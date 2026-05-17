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
            publicUrl: route('public-label', $monitoring)
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
}
