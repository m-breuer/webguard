<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringWidgetPayload;
use App\Data\MonitoringWidgetUptimePayload;
use App\Models\Monitoring;
use App\Support\MonitoringDateRange;
use App\Support\MonitoringStatusMeta;
use Illuminate\Support\Facades\Date;

class MonitoringWidgetPayloadService
{
    public function __construct(
        private readonly MonitoringStatusService $monitoringStatusService,
        private readonly MonitoringAvailabilityService $monitoringAvailabilityService
    ) {}

    public function getPayload(Monitoring $monitoring): MonitoringWidgetPayload
    {
        $statusSince = $this->monitoringStatusService->getStatusSince($monitoring);
        $statusNow = $this->monitoringStatusService->getStatusNow($monitoring);
        $latestStatusCode = $monitoring->latestResponseResult?->http_status_code;
        $status = (string) ($statusSince['status'] ?? 'unknown');
        $checkedAt = $statusNow['checked_at'] ?? null;
        $uptimePercentages = $this->resolveUptimePercentages($monitoring, [7, 30, 365]);
        $incidentCounts = $this->resolveIncidentCounts($monitoring, [30, 90, 365]);

        return new MonitoringWidgetPayload(
            name: $monitoring->name,
            status: $status,
            statusLabel: mb_strtoupper($status),
            statusCode: $latestStatusCode,
            statusIdentifier: MonitoringStatusMeta::statusIdentifier($latestStatusCode, $monitoring->isUnderMaintenance()),
            statusKey: MonitoringStatusMeta::statusKey($latestStatusCode, $monitoring->isUnderMaintenance()),
            checkedAt: $checkedAt,
            checkedAtHuman: $checkedAt ? Date::parse((string) $checkedAt)->diffForHumans() : null,
            uptime: new MonitoringWidgetUptimePayload(
                sevenDays: $uptimePercentages[7] ?? null,
                thirtyDays: $uptimePercentages[30] ?? null,
                year: $uptimePercentages[365] ?? null
            ),
            publicUrl: route('public-label', $monitoring),
            incidents: [
                '30_days' => $incidentCounts[30] ?? 0,
                '90_days' => $incidentCounts[90] ?? 0,
                '365_days' => $incidentCounts[365] ?? 0,
            ],
            ssl: [
                'valid' => $monitoring->sslResult?->is_valid,
                'expires_at' => $monitoring->sslResult?->expires_at?->toIso8601String(),
            ],
            domain: [
                'valid' => $monitoring->domainResult?->is_valid,
                'expires_at' => $monitoring->domainResult?->expires_at?->toIso8601String(),
            ],
            maintenance: [
                'active' => $monitoring->isUnderMaintenance(),
                'starts_at' => $monitoring->maintenance_from?->toIso8601String(),
                'ends_at' => $monitoring->maintenance_until?->toIso8601String(),
            ]
        );
    }

    /**
     * @param  array<int, int>  $days
     * @return array<int, float|null>
     */
    private function resolveUptimePercentages(Monitoring $monitoring, array $days): array
    {
        if ($monitoring->created_at->diffInDays(Date::now()) < 1) {
            return collect($days)
                ->mapWithKeys(function (int $day) use ($monitoring): array {
                    $monitoringDateRange = MonitoringDateRange::pastDays($day);

                    return [
                        $day => data_get($this->monitoringAvailabilityService->getUptimeDowntime(
                            $monitoring,
                            $monitoringDateRange->startDate,
                            $monitoringDateRange->endDate,
                            false,
                            false
                        ), 'uptime.percentage'),
                    ];
                })
                ->all();
        }

        $statsByRange = $this->monitoringAvailabilityService->getUptimeDowntimesForRanges($monitoring, $days);

        return collect($days)
            ->mapWithKeys(fn (int $day): array => [
                $day => data_get($statsByRange, $day . '.uptime.percentage'),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $days
     * @return array<int, int>
     */
    private function resolveIncidentCounts(Monitoring $monitoring, array $days): array
    {
        $oldestRange = max($days);
        $incidents = $monitoring->incidents()
            ->where('down_at', '>=', Date::now()->subDays($oldestRange))
            ->get(['down_at']);

        return collect($days)
            ->mapWithKeys(fn (int $day): array => [
                $day => $incidents
                    ->filter(fn ($incident): bool => $incident->down_at->greaterThanOrEqualTo(Date::now()->subDays($day)))
                    ->count(),
            ])
            ->all();
    }
}
