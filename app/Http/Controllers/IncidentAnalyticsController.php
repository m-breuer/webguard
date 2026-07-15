<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IncidentCustomerImpact;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Http\Requests\IncidentAnalyticsRequest;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class IncidentAnalyticsController extends Controller
{
    public function index(IncidentAnalyticsRequest $request): View
    {
        $filters = $request->validated();
        $days = (int) ($filters['days'] ?? 90);
        $incidents = $this->incidents($filters, $days);
        $resolvedIncidents = $incidents->filter(static fn (Incident $incident): bool => $incident->up_at !== null);
        $durations = $resolvedIncidents->map(
            static fn (Incident $incident): int => (int) $incident->down_at->diffInMinutes($incident->up_at)
        );

        return view('incidents.analytics', [
            'filters' => [
                'days' => $days,
                'incident_type' => $filters['incident_type'] ?? null,
                'severity' => $filters['severity'] ?? null,
                'customer_impact' => $filters['customer_impact'] ?? null,
                'affected_service' => $filters['affected_service'] ?? null,
            ],
            'incidents' => $incidents,
            'totalCount' => $incidents->count(),
            'resolvedCount' => $resolvedIncidents->count(),
            'openCount' => $incidents->reject(static fn (Incident $incident): bool => $incident->up_at !== null)->count(),
            'mttrMinutes' => $durations->isEmpty() ? null : (int) round($durations->avg()),
            'byType' => $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->incident_type?->value ?? 'unclassified'),
            'bySeverity' => $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->severity?->value ?? 'unclassified'),
            'byImpact' => $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->customer_impact?->value ?? 'unclassified'),
            'byService' => $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->affected_service ?: $incident->monitoring->name),
            'repeatServices' => $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->affected_service ?: $incident->monitoring->name)
                ->filter(static fn (int $count): bool => $count > 1),
            'incidentTypes' => IncidentType::cases(),
            'severities' => IncidentSeverity::cases(),
            'customerImpacts' => IncidentCustomerImpact::cases(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Incident>
     */
    private function incidents(array $filters, int $days): Collection
    {
        $query = Incident::query()
            ->with('monitoring')
            ->whereBetween('down_at', [Date::now()->subDays($days)->startOfDay(), Date::now()->endOfDay()])
            ->latest('down_at');

        foreach (['incident_type', 'severity', 'customer_impact'] as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['affected_service'])) {
            $query->where('affected_service', 'like', '%' . $filters['affected_service'] . '%');
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Incident>  $incidents
     * @return \Illuminate\Support\Collection<string, int>
     */
    private function groupCounts(Collection $incidents, callable $keyResolver): \Illuminate\Support\Collection
    {
        return $incidents
            ->groupBy($keyResolver)
            ->map(static fn (Collection $group): int => $group->count())
            ->sortDesc();
    }
}
