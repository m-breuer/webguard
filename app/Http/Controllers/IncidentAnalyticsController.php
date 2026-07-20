<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IncidentCustomerImpact;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Enums\MonitoringStatus;
use App\Enums\StatusPageComponentSource;
use App\Http\Requests\IncidentAnalyticsRequest;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use App\Services\MonitoringOverviewService;
use App\Services\MonitoringStatusService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class IncidentAnalyticsController extends Controller
{
    public function index(
        IncidentAnalyticsRequest $incidentAnalyticsRequest,
        MonitoringOverviewService $monitoringOverviewService,
        MonitoringStatusService $monitoringStatusService
    ): View {
        $filters = $incidentAnalyticsRequest->validated();
        $days = (int) ($filters['days'] ?? 90);

        if (! $incidentAnalyticsRequest->boolean('async')) {
            return view('incidents.analytics-shell');
        }

        $section = $incidentAnalyticsRequest->string('section')->toString();
        if ($section !== '') {
            return $this->section(
                $section,
                $filters,
                $days,
                $incidentAnalyticsRequest,
                $monitoringOverviewService,
                $monitoringStatusService,
            );
        }

        $incidents = $this->incidents($filters, $days);
        $resolvedIncidents = $incidents->filter(static fn (Incident $incident): bool => $incident->up_at !== null);
        $durations = $resolvedIncidents->map(
            static fn (Incident $incident): int => (int) $incident->down_at->diffInMinutes($incident->up_at)
        );
        $monitoringGroups = $incidentAnalyticsRequest->user()
            ->monitoringGroups()
            ->withCount('monitorings')
            ->with(['monitorings.latestResponseResult', 'monitorings.latestIncident'])
            ->orderBy('name')
            ->get();
        $statusPages = $incidentAnalyticsRequest->user()
            ->statusPages()
            ->withCount('components')
            ->with([
                'components.monitorings.latestResponseResult',
                'components.monitorings.latestIncident',
                'components.monitoringGroup.monitorings.latestResponseResult',
                'components.monitoringGroup.monitorings.latestIncident',
            ])
            ->latest()
            ->get();
        $overview = $monitoringOverviewService->overview($incidentAnalyticsRequest->user());

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
            'monitoringGroups' => $monitoringGroups->map(fn (MonitoringGroup $monitoringGroup): array => [
                'model' => $monitoringGroup,
                'summary' => $this->summarizeMonitorings($monitoringGroup->monitorings, $monitoringStatusService),
            ]),
            'statusPages' => $statusPages->map(function (StatusPage $statusPage) use ($monitoringStatusService): array {
                $monitorings = $this->statusPageMonitorings($statusPage);

                return [
                    'model' => $statusPage,
                    'summary' => $this->summarizeMonitorings($monitorings, $monitoringStatusService),
                ];
            }),
            'serviceSummary' => $overview['summary'],
            'overallState' => $overview['overallState'],
            'incidentTrend' => $this->incidentTrend($incidents, $days),
        ]);
    }

    private function section(
        string $section,
        array $filters,
        int $days,
        IncidentAnalyticsRequest $request,
        MonitoringOverviewService $monitoringOverviewService,
        MonitoringStatusService $monitoringStatusService,
    ): View {
        return match ($section) {
            'overview' => view('incidents.analytics-sections.overview', [
                'overview' => $monitoringOverviewService->overview($request->user()),
            ]),
            'groups' => view('incidents.analytics-sections.groups', [
                'groups' => $this->monitoringGroups($request, $monitoringStatusService),
            ]),
            'status-pages' => view('incidents.analytics-sections.status-pages', [
                'statusPages' => $this->statusPages($request, $monitoringStatusService),
            ]),
            'incidents' => view('incidents.analytics-sections.incidents', $this->incidentSectionData($filters, $days)),
            default => abort(404),
        };
    }

    /**
     * @return array{groups:Collection<int, array{model:MonitoringGroup,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}>,statusPages:Collection<int, array{model:StatusPage,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}>}
     */
    private function monitoringGroups(IncidentAnalyticsRequest $request, MonitoringStatusService $monitoringStatusService): Collection
    {
        return $request->user()
            ->monitoringGroups()
            ->withCount('monitorings')
            ->with(['monitorings.latestResponseResult', 'monitorings.latestIncident'])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (MonitoringGroup $monitoringGroup): array => [
                'model' => $monitoringGroup,
                'summary' => $this->summarizeMonitorings($monitoringGroup->monitorings, $monitoringStatusService),
            ]);
    }

    /**
     * @return Collection<int, array{model:StatusPage,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}>
     */
    private function statusPages(IncidentAnalyticsRequest $request, MonitoringStatusService $monitoringStatusService): Collection
    {
        return $request->user()
            ->statusPages()
            ->withCount('components')
            ->with([
                'components.monitorings.latestResponseResult',
                'components.monitorings.latestIncident',
                'components.monitoringGroup.monitorings.latestResponseResult',
                'components.monitoringGroup.monitorings.latestIncident',
            ])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (StatusPage $statusPage) use ($monitoringStatusService): array {
                return [
                    'model' => $statusPage,
                    'summary' => $this->summarizeMonitorings($this->statusPageMonitorings($statusPage), $monitoringStatusService),
                ];
            });
    }

    /**
     * @return array{filters:array{days:int,incident_type:string|null,severity:string|null,customer_impact:string|null,affected_service:string|null},incidents:EloquentCollection<int, Incident>,totalCount:int,resolvedCount:int,openCount:int,mttrMinutes:int|null,byType:Collection<string,int>,bySeverity:Collection<string,int>,byImpact:Collection<string,int>,byService:Collection<string,int>,repeatServices:Collection<string,int>,incidentTypes:list<IncidentType>,severities:list<IncidentSeverity>,customerImpacts:list<IncidentCustomerImpact>,incidentTrend:array{points:list<array{label:string,count:int,x:float,y:float}>,max:int}}
     */
    private function incidentSectionData(array $filters, int $days): array
    {
        $incidents = $this->incidents($filters, $days);
        $resolvedIncidents = $incidents->filter(static fn (Incident $incident): bool => $incident->up_at !== null);
        $durations = $resolvedIncidents->map(static fn (Incident $incident): int => (int) $incident->down_at->diffInMinutes($incident->up_at));
        $byService = $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->affected_service ?: $incident->monitoring->name);

        return [
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
            'byService' => $byService,
            'repeatServices' => $byService->filter(static fn (int $count): bool => $count > 1),
            'incidentTypes' => IncidentType::cases(),
            'severities' => IncidentSeverity::cases(),
            'customerImpacts' => IncidentCustomerImpact::cases(),
            'incidentTrend' => $this->incidentTrend($incidents, $days),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return EloquentCollection<int, Incident>
     */
    private function incidents(array $filters, int $days): EloquentCollection
    {
        $builder = Incident::query()
            ->whereHas('monitoring')
            ->with('monitoring')
            ->whereBetween('down_at', [Date::now()->subDays($days)->startOfDay(), Date::now()->endOfDay()])
            ->latest('down_at');

        foreach (['incident_type', 'severity', 'customer_impact'] as $filter) {
            if (! empty($filters[$filter])) {
                $builder->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['affected_service'])) {
            $builder->where('affected_service', 'like', '%' . $filters['affected_service'] . '%');
        }

        return $builder->get();
    }

    /**
     * @param  EloquentCollection<int, Incident>  $eloquentCollection
     * @return Collection<string, int>
     */
    private function groupCounts(EloquentCollection $eloquentCollection, callable $keyResolver): Collection
    {
        return $eloquentCollection
            ->groupBy($keyResolver)
            ->map(static fn (Collection $group): int => $group->count())
            ->sortDesc();
    }

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     * @return array{total:int,healthy:int,down:int,attention:int,state:string}
     */
    private function summarizeMonitorings(Collection $monitorings, MonitoringStatusService $monitoringStatusService): array
    {
        $states = $monitorings->map(
            fn (Monitoring $monitoring): string => $this->monitoringState($monitoring, $monitoringStatusService)
        );
        $down = $states->filter(static fn (string $state): bool => $state === MonitoringStatus::DOWN->value)->count();
        $healthy = $states->filter(static fn (string $state): bool => $state === MonitoringStatus::UP->value)->count();
        $attention = $states->filter(
            static fn (string $state): bool => in_array($state, ['unknown', 'paused', 'maintenance'], true)
        )->count();

        return [
            'total' => $states->count(),
            'healthy' => $healthy,
            'down' => $down,
            'attention' => $attention,
            'state' => $states->isEmpty() ? 'new' : ($down > 0 ? 'degraded' : ($attention > 0 ? 'attention' : 'healthy')),
        ];
    }

    private function monitoringState(Monitoring $monitoring, MonitoringStatusService $monitoringStatusService): string
    {
        if ($monitoring->isPaused()) {
            return 'paused';
        }

        if ($monitoring->isUnderMaintenance()) {
            return 'maintenance';
        }

        if ($monitoring->latestIncident !== null && $monitoring->latestIncident->up_at === null) {
            return MonitoringStatus::DOWN->value;
        }

        $status = $monitoringStatusService->getStatusNow($monitoring)['status'];

        return $status instanceof MonitoringStatus ? $status->value : (string) $status;
    }

    /**
     * @return Collection<int, Monitoring>
     */
    private function statusPageMonitorings(StatusPage $statusPage): Collection
    {
        return $statusPage->components
            ->flatMap(static function (StatusPageComponent $statusPageComponent): Collection {
                if ($statusPageComponent->source_type === StatusPageComponentSource::MONITORING_GROUP) {
                    return $statusPageComponent->monitoringGroup?->monitorings ?? collect();
                }

                return $statusPageComponent->monitorings;
            })
            ->unique('id')
            ->values();
    }

    /**
     * @return array{points:list<array{label:string,count:int,x:float,y:float}>,max:int}
     */
    private function incidentTrend(EloquentCollection $eloquentCollection, int $days): array
    {
        $pointCount = $days >= 90 ? 10 : 7;
        $bucketDays = max(1, (int) ceil($days / $pointCount));
        $periodStart = Date::now()->subDays($days - 1)->startOfDay();
        $now = Date::now();
        $points = collect();

        for ($index = 0; $index < $pointCount; $index++) {
            $bucketStart = $periodStart->copy()->addDays($index * $bucketDays);

            if ($bucketStart->isAfter($now)) {
                break;
            }

            $bucketEnd = $bucketStart->copy()->addDays($bucketDays - 1)->endOfDay();
            $bucketEnd = $bucketEnd->isAfter($now) ? $now : $bucketEnd;
            $count = $eloquentCollection->filter(
                static fn (Incident $incident): bool => $incident->down_at->betweenIncluded($bucketStart, $bucketEnd)
            )->count();

            $points->push([
                'label' => $bucketStart->locale(app()->getLocale())->isoFormat('D. MMM'),
                'count' => $count,
            ]);
        }

        $max = max(1, (int) $points->max('count'));
        $lastIndex = max(1, $points->count() - 1);

        return [
            'points' => $points->values()->map(
                static fn (array $point, int $index): array => [
                    ...$point,
                    'x' => round(($index / $lastIndex) * 100, 2),
                    'y' => round(78 - (($point['count'] / $max) * 58), 2),
                ]
            )->all(),
            'max' => $max,
        ];
    }
}
