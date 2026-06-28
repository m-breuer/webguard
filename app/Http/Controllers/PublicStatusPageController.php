<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MonitoringStatus;
use App\Enums\StatusPageComponentSource;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use App\Services\MonitoringStatusService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class PublicStatusPageController extends Controller
{
    public function __construct(
        private readonly MonitoringStatusService $monitoringStatusService
    ) {}

    public function __invoke(StatusPage $statusPage): View
    {
        abort_unless($statusPage->is_public, 404);

        $statusPage->loadMissing([
            'components.monitorings' => fn ($query) => $query->withoutGlobalScope('user')
                ->with(['latestIncident', 'latestResponseResult']),
            'components.monitoringGroup.monitorings' => fn ($query) => $query->withoutGlobalScope('user')
                ->with(['latestIncident', 'latestResponseResult'])
                ->orderBy('name'),
        ]);

        $components = $statusPage->components->map(function (StatusPageComponent $statusPageComponent): array {
            $monitorings = $this->componentMonitorings($statusPageComponent)->map(function (Monitoring $monitoring): array {
                $status = $this->monitoringStatus($monitoring);

                return [
                    'model' => $monitoring,
                    'status' => $status,
                    'badgeType' => $this->statusBadgeType($status),
                    'isUnderMaintenance' => $monitoring->isUnderMaintenance(),
                    'lastCheckedAt' => $monitoring->latestResponseResult?->updated_at,
                ];
            });

            $status = $this->aggregateStatus($monitorings->pluck('status'));

            return [
                'model' => $statusPageComponent,
                'status' => $status,
                'badgeType' => $this->statusBadgeType($status),
                'monitorings' => $monitorings,
                'hasMaintenance' => $monitorings->contains(
                    static fn (array $monitoring): bool => $monitoring['isUnderMaintenance'] === true
                ),
            ];
        });

        $pageStatus = $this->aggregateStatus($components->pluck('status'));

        return view('status-pages.public-show', [
            'statusPage' => $statusPage,
            'pageStatus' => $pageStatus,
            'pageStatusBadgeType' => $this->statusBadgeType($pageStatus),
            'components' => $components,
            'incidents' => $this->recentIncidents($statusPage),
        ]);
    }

    private function monitoringStatus(Monitoring $monitoring): string
    {
        $statusSince = $this->monitoringStatusService->getStatusSince($monitoring);
        $statusNow = $this->monitoringStatusService->getStatusNow($monitoring);

        return $this->normalizeStatus($statusSince['status'] ?? $statusNow['status'] ?? MonitoringStatus::UNKNOWN->value);
    }

    /**
     * @param  Collection<int, string>  $statuses
     */
    private function aggregateStatus(Collection $statuses): string
    {
        if ($statuses->isEmpty()) {
            return MonitoringStatus::UNKNOWN->value;
        }

        if ($statuses->contains(MonitoringStatus::DOWN->value)) {
            return MonitoringStatus::DOWN->value;
        }

        if ($statuses->contains(MonitoringStatus::UNKNOWN->value)) {
            return MonitoringStatus::UNKNOWN->value;
        }

        return MonitoringStatus::UP->value;
    }

    private function normalizeStatus(mixed $status): string
    {
        if ($status instanceof MonitoringStatus) {
            return $status->value;
        }

        $normalized = mb_strtolower((string) $status);

        return MonitoringStatus::tryFrom($normalized)?->value ?? MonitoringStatus::UNKNOWN->value;
    }

    private function statusBadgeType(string $status): string
    {
        return match ($status) {
            MonitoringStatus::UP->value => 'success',
            MonitoringStatus::DOWN->value => 'danger',
            default => 'warning',
        };
    }

    /**
     * @return Collection<int, Monitoring>
     */
    private function componentMonitorings(StatusPageComponent $statusPageComponent): Collection
    {
        if ($statusPageComponent->source_type === StatusPageComponentSource::MONITORING_GROUP) {
            return $statusPageComponent->monitoringGroup?->monitorings ?? collect();
        }

        return $statusPageComponent->monitorings;
    }

    /**
     * @return Collection<int, Incident>
     */
    private function recentIncidents(StatusPage $statusPage): Collection
    {
        $monitoringIds = $statusPage->components
            ->flatMap(fn (StatusPageComponent $statusPageComponent): Collection => $this->componentMonitorings($statusPageComponent)->pluck('id'))
            ->unique()
            ->values();

        if ($monitoringIds->isEmpty()) {
            return collect();
        }

        return Incident::query()
            ->with([
                'monitoring' => fn ($query) => $query->withoutGlobalScope('user'),
                'updates',
            ])
            ->whereIn('monitoring_id', $monitoringIds)
            ->whereBetween('down_at', [Date::now()->subDays(90)->startOfDay(), Date::now()->endOfDay()])
            ->latest('down_at')
            ->limit(10)
            ->get();
    }
}
