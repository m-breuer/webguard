<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringDashboardPayload;
use App\Data\MonitoringSslPayload;
use App\Models\Monitoring;
use App\Support\MonitoringDateRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

class MonitoringDashboardPayloadService
{
    public function __construct(
        private readonly MonitoringStatusService $monitoringStatusService,
        private readonly MonitoringAvailabilityService $monitoringAvailabilityService,
        private readonly MonitoringResponseTimeService $monitoringResponseTimeService,
        private readonly MonitoringIncidentService $monitoringIncidentService,
        private readonly MonitoringHeatmapService $monitoringHeatmapService,
        private readonly MonitoringUptimeCalendarService $monitoringUptimeCalendarService
    ) {}

    public function getPayload(Monitoring $monitoring, int $days, Carbon $calendarStartDate, Carbon $calendarEndDate): MonitoringDashboardPayload
    {
        $range = MonitoringDateRange::pastDays($days);
        $heatmapStartDate = Date::now()->subHours(23);
        $heatmapEndDate = Date::now();

        return new MonitoringDashboardPayload(
            statusSince: $this->monitoringStatusService->getStatusSince($monitoring),
            statusNow: $this->monitoringStatusService->getStatusNow($monitoring),
            uptimeDowntime: $this->monitoringAvailabilityService->getUptimeDowntime(
                $monitoring,
                $range->startDate,
                $range->endDate,
                $range->shouldUseUptimeAggregates($monitoring),
                $range->shouldIncludeIntradayRawData()
            ),
            responseTimes: $this->monitoringResponseTimeService->getResponseTimes(
                $monitoring,
                $range->startDate,
                $range->endDate,
                $range->shouldUseResponseTimeAggregates()
            ),
            incidents: $this->monitoringIncidentService->getIncidents($monitoring, $range->startDate, $range->endDate),
            heatmap: $this->monitoringHeatmapService->getHeatmap($monitoring, $heatmapStartDate, $heatmapEndDate),
            ssl: $this->getSslPayload($monitoring),
            uptimeCalendar: $this->monitoringUptimeCalendarService->getGroupedByDateAndMonth(
                $monitoring,
                $calendarStartDate,
                $calendarEndDate
            ),
        );
    }

    public function getSslPayload(Monitoring $monitoring): MonitoringSslPayload
    {
        return new MonitoringSslPayload(
            valid: $monitoring->sslResult?->is_valid,
            expiration: optional($monitoring->sslResult?->expires_at)?->toIso8601String(),
            issuer: $monitoring->sslResult?->issuer,
            issueDate: optional($monitoring->sslResult?->issued_at)?->toIso8601String(),
        );
    }
}
