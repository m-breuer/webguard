<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Services\MonitoringStatusService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class PublicMonitoringGroupLabelController extends Controller
{
    public function __construct(
        private readonly MonitoringStatusService $monitoringStatusService
    ) {}

    public function __invoke(MonitoringGroup $monitoringGroup): View
    {
        abort_unless($monitoringGroup->public_label_enabled, 404);

        $monitoringGroup->load([
            'monitorings' => function ($builder): void {
                $builder->withoutGlobalScope('user')
                    ->where('public_label_enabled', true)
                    ->with(['latestIncident', 'latestResponseResult'])
                    ->orderBy('name');
            },
        ]);

        /** @var Collection<int, Monitoring> $monitorings */
        $monitorings = $monitoringGroup->monitorings;

        return view('monitoring-groups.public-label', [
            'monitoringGroup' => $monitoringGroup,
            'monitorings' => $monitorings,
            'statusSummaries' => $this->statusSummaries($monitorings),
        ]);
    }

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     * @return array<string, array{status: string, badge: string, since: mixed}>
     */
    private function statusSummaries(Collection $monitorings): array
    {
        return $monitorings
            ->mapWithKeys(function (Monitoring $monitoring): array {
                $statusSince = $this->monitoringStatusService->getStatusSince($monitoring);
                $statusNow = $this->monitoringStatusService->getStatusNow($monitoring);
                $status = $this->normalizeStatus($statusSince['status'] ?? $statusNow['status'] ?? MonitoringStatus::UNKNOWN->value);

                return [
                    $monitoring->id => [
                        'status' => $status,
                        'badge' => $this->statusBadgeType($status),
                        'since' => $statusSince['since'] ?? null,
                    ],
                ];
            })
            ->all();
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
}
