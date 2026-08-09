<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitoring;
use App\Models\User;
use App\Support\MonitoringDateRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

final class MobileMonitoringDetailPayloadService
{
    public function __construct(
        private readonly MonitoringAvailabilityService $monitoringAvailabilityService,
        private readonly MonitoringResponseTimeService $monitoringResponseTimeService,
        private readonly MonitoringIncidentService $monitoringIncidentService,
        private readonly MonitoringHeatmapService $monitoringHeatmapService,
        private readonly MonitoringUptimeCalendarService $monitoringUptimeCalendarService,
        private readonly MonitoringDashboardPayloadService $monitoringDashboardPayloadService,
        private readonly MonitoringStatusPayloadService $monitoringStatusPayloadService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Monitoring $monitoring, User $user, int $days, int $incidentLimit, int $incidentOffset): array
    {
        $generatedAt = Date::now();
        $monitoringDateRange = MonitoringDateRange::pastDays($days);
        $currentCheck = $this->monitoringStatusPayloadService->getPayload($monitoring, false)->toArray();
        $availability = $this->monitoringAvailabilityService->getUptimeDowntime(
            $monitoring,
            $monitoringDateRange->startDate,
            $monitoringDateRange->endDate,
            $monitoringDateRange->shouldUseUptimeAggregates($monitoring),
            $monitoringDateRange->shouldIncludeIntradayRawData(),
        )->toArray();
        $responseTimes = $this->monitoringResponseTimeService->getResponseTimes(
            $monitoring,
            $monitoringDateRange->startDate,
            $monitoringDateRange->endDate,
            $monitoringDateRange->shouldUseResponseTimeAggregates(),
        )->toArray();
        $incidents = $this->monitoringIncidentService->getIncidentPage(
            $monitoring,
            $monitoringDateRange->startDate,
            $monitoringDateRange->endDate,
            $incidentLimit,
            $incidentOffset,
        );
        $heatmap = $this->monitoringHeatmapService
            ->getHeatmap($monitoring, $generatedAt->copy()->subHours(23), $generatedAt)
            ->map(static fn (array $point): array => [
                'date' => $point['date']->toIso8601String(),
                'uptime' => $point['uptime'],
                'downtime' => $point['downtime'],
                'unknown' => $point['unknown'],
            ])
            ->values()
            ->all();
        $calendar = $this->monitoringUptimeCalendarService
            ->getGroupedByDateAndMonth($monitoring, $monitoringDateRange->startDate, $monitoringDateRange->endDate)
            ->toArray();
        $ssl = $this->monitoringDashboardPayloadService->getSslPayload($monitoring)->toArray();
        $domain = $this->domainPayload($monitoring);

        return [
            'data' => [
                'summary' => $this->summaryPayload($monitoring, $user),
                'current_check' => $currentCheck,
                'availability' => $availability,
                'response_times' => $responseTimes,
                'incidents' => $incidents['data'],
                'heatmap' => $heatmap,
                'maintenance' => [
                    'active' => $monitoring->isUnderMaintenance(),
                    'starts_at' => $monitoring->maintenance_from?->toIso8601String(),
                    'ends_at' => $monitoring->maintenance_until?->toIso8601String(),
                    'has_recurring_window' => $monitoring->has_enabled_maintenance_windows,
                ],
                'ssl' => $ssl,
                'domain' => $domain,
                'uptime_calendar' => $calendar,
                'capabilities' => [
                    'can_manage' => $monitoring->isManageableBy($user),
                ],
            ],
            'meta' => [
                'generated_at' => $generatedAt->toIso8601String(),
                'range' => [
                    'days' => $days,
                    'from' => $monitoringDateRange->startDate->toIso8601String(),
                    'to' => $monitoringDateRange->endDate->toIso8601String(),
                ],
                'incidents' => [
                    'limit' => $incidentLimit,
                    'offset' => $incidentOffset,
                    'has_more' => $incidents['has_more'],
                    'next_offset' => $incidents['next_offset'],
                ],
                'sections' => [
                    'summary' => $this->sectionMeta('current', $generatedAt),
                    'current_check' => $this->sectionMeta($this->currentCheckState($currentCheck, $generatedAt), $generatedAt),
                    'availability' => $this->sectionMeta($availability['has_data'] ? 'current' : 'empty', $generatedAt),
                    'response_times' => $this->sectionMeta($responseTimes['data']->isEmpty() ? 'empty' : 'current', $generatedAt),
                    'incidents' => $this->sectionMeta($incidents['data'] === [] ? 'empty' : 'current', $generatedAt),
                    'heatmap' => $this->sectionMeta($availability['has_data'] ? 'current' : 'empty', $generatedAt),
                    'maintenance' => $this->sectionMeta('current', $generatedAt),
                    'ssl' => $this->sectionMeta($monitoring->sslResult === null ? 'unavailable' : 'current', $generatedAt),
                    'domain' => $this->sectionMeta($domain === null ? 'unavailable' : 'current', $generatedAt),
                    'uptime_calendar' => $this->sectionMeta($this->calendarHasData($calendar) ? 'current' : 'empty', $generatedAt),
                    'capabilities' => $this->sectionMeta('current', $generatedAt),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryPayload(Monitoring $monitoring, User $user): array
    {
        return [
            'id' => $monitoring->getKey(),
            'name' => $monitoring->name,
            'target' => $monitoring->target,
            'type' => $monitoring->type->value,
            'lifecycle_status' => $monitoring->status->value,
            'ownership' => [
                'type' => $monitoring->isTeamOwned() ? 'team' : 'private',
                'can_manage' => $monitoring->isManageableBy($user),
            ],
            'performance' => $monitoring->performanceState ? [
                'status' => $monitoring->performanceState->status?->value,
                'consecutive_breaches' => $monitoring->performanceState->consecutive_breaches,
                'degraded_at' => $monitoring->performanceState->degraded_at?->toIso8601String(),
            ] : null,
            'open_incident' => $monitoring->latestIncident?->up_at === null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function domainPayload(Monitoring $monitoring): ?array
    {
        if ($monitoring->domainResult === null) {
            return null;
        }

        return [
            'valid' => $monitoring->domainResult->is_valid,
            'expires_at' => $monitoring->domainResult->expires_at?->toIso8601String(),
            'registrar' => $monitoring->domainResult->registrar,
            'checked_at' => $monitoring->domainResult->checked_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $currentCheck
     */
    private function currentCheckState(array $currentCheck, Carbon $generatedAt): string
    {
        $checkedAt = $currentCheck['checked_at'] ?? null;

        if (! is_string($checkedAt)) {
            return 'empty';
        }

        $interval = max((int) ($currentCheck['interval'] ?? 0), 60);

        return Date::parse($checkedAt)->lt($generatedAt->copy()->subSeconds($interval * 2))
            ? 'stale'
            : 'current';
    }

    /**
     * @return array{state: string, generated_at: string}
     */
    private function sectionMeta(string $state, Carbon $generatedAt): array
    {
        return [
            'state' => $state,
            'generated_at' => $generatedAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, array{days: list<array{date: string, uptime_percentage: float|null}>, monthly_average_uptime: float|null}>  $calendar
     */
    private function calendarHasData(array $calendar): bool
    {
        foreach ($calendar as $month) {
            foreach ($month['days'] as $day) {
                if ($day['uptime_percentage'] !== null) {
                    return true;
                }
            }
        }

        return false;
    }
}
