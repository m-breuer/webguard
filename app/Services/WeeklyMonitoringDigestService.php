<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringAvailabilityPayload;
use App\Data\MonitoringAvailabilitySegmentPayload;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringSslResult;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class WeeklyMonitoringDigestService
{
    /**
     * @return array{
     *     period_start: Carbon,
     *     period_end: Carbon,
     *     frequency: string,
     *     overview: array{uptime_percentage: float|null, incidents_count: int, longest_downtime_minutes: int, monitorings_count: int},
     *     monitorings: list<array{name: string, target: string, uptime_percentage: float|null, incidents_count: int, downtime_minutes: int, longest_downtime_minutes: int}>,
     *     ssl_warnings: list<array{name: string, target: string, expires_at: Carbon|null, is_valid: bool}>,
     *     domain_warnings: list<array{name: string, target: string, expires_at: Carbon|null, is_valid: bool}>
     * }
     */
    public function buildForUser(User $user, ?Carbon $periodEnd = null, string $frequency = 'weekly'): array
    {
        $periodEnd = ($periodEnd ?? Date::now()->subDay())->copy()->endOfDay();
        $periodDays = match ($frequency) {
            'daily' => 1,
            'monthly' => 30,
            default => 7,
        };
        $periodStart = $periodEnd->copy()->subDays($periodDays - 1)->startOfDay();

        $monitorings = Monitoring::query()
            ->visibleTo($user)
            ->active()
            ->with([
                'sslResult',
                'domainResult',
                'dailyResults' => fn ($query) => $query
                    ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->orderBy('date')
                    ->select([
                        'monitoring_id',
                        'date',
                        'uptime_minutes',
                        'downtime_minutes',
                        'unknown_minutes',
                        'uptime_total',
                        'downtime_total',
                        'unknown_total',
                        'incidents_count',
                    ]),
                'incidents' => fn ($query) => $query
                    ->where('down_at', '<=', $periodEnd)
                    ->where(function ($builder) use ($periodStart): void {
                        $builder->where('up_at', '>=', $periodStart)
                            ->orWhereNull('up_at');
                    })
                    ->select(['monitoring_id', 'down_at', 'up_at']),
            ])
            ->orderBy('name')
            ->get();

        $monitoringRows = [];
        $totalUptimeMinutes = 0;
        $totalDowntimeMinutes = 0;
        $totalUnknownMinutes = 0;
        $totalIncidents = 0;
        $longestDowntimeMinutes = 0;
        $sslWarnings = [];
        $domainWarnings = [];
        $warningThreshold = $periodEnd
            ->copy()
            ->addDays((int) config('monitoring.digest_expiry_warning_days', 30))
            ->endOfDay();

        foreach ($monitorings as $monitoring) {
            $uptimeDowntime = $this->buildAvailabilityFromLoadedDailyResults($monitoring, $periodStart, $periodEnd);
            $maintenanceWindow = $this->getOverlappingMaintenanceWindow($monitoring, $periodStart, $periodEnd);
            $maintenanceMinutes = $this->getMaintenanceMinutes($maintenanceWindow);

            $incidentDurations = $this->getOverlappingIncidentDurations($monitoring->incidents, $periodStart, $periodEnd, $maintenanceWindow);
            $incidentsCount = count($incidentDurations);
            $monitoringLongestDowntimeMinutes = empty($incidentDurations) ? 0 : max($incidentDurations);

            $uptimeMinutes = $uptimeDowntime->uptime->minutes;
            $downtimeMinutes = $uptimeDowntime->downtime->minutes;
            $unknownMinutes = $uptimeDowntime->unknown->minutes;
            $this->removeMaintenanceMinutes($maintenanceMinutes, $uptimeMinutes, $downtimeMinutes, $unknownMinutes);
            $monitoringTrackedMinutes = $uptimeMinutes + $downtimeMinutes + $unknownMinutes;

            $totalUptimeMinutes += $uptimeMinutes;
            $totalDowntimeMinutes += $downtimeMinutes;
            $totalUnknownMinutes += $unknownMinutes;
            $totalIncidents += $incidentsCount;
            $longestDowntimeMinutes = max($longestDowntimeMinutes, $monitoringLongestDowntimeMinutes);

            $monitoringRows[] = [
                'name' => $monitoring->name,
                'target' => $monitoring->target,
                'uptime_percentage' => $monitoringTrackedMinutes > 0 ? ($uptimeMinutes / $monitoringTrackedMinutes) * 100 : null,
                'incidents_count' => $incidentsCount,
                'downtime_minutes' => $downtimeMinutes,
                'longest_downtime_minutes' => $monitoringLongestDowntimeMinutes,
            ];

            if ($this->expiresSoonOrIsInvalid($monitoring->sslResult, $warningThreshold)) {
                $sslWarnings[] = $this->buildExpiryWarning($monitoring, $monitoring->sslResult);
            }

            if ($this->expiresSoonOrIsInvalid($monitoring->domainResult, $warningThreshold)) {
                $domainWarnings[] = $this->buildExpiryWarning($monitoring, $monitoring->domainResult);
            }
        }

        $totalTrackedMinutes = $totalUptimeMinutes + $totalDowntimeMinutes + $totalUnknownMinutes;

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'frequency' => $frequency,
            'overview' => [
                'uptime_percentage' => $totalTrackedMinutes > 0 ? ($totalUptimeMinutes / $totalTrackedMinutes) * 100 : null,
                'incidents_count' => $totalIncidents,
                'longest_downtime_minutes' => $longestDowntimeMinutes,
                'monitorings_count' => $monitorings->count(),
            ],
            'monitorings' => $monitoringRows,
            'ssl_warnings' => $sslWarnings,
            'domain_warnings' => $domainWarnings,
        ];
    }

    /**
     * @param  Collection<int, Incident>  $incidents
     * @param  array{from: Carbon, until: Carbon}|null  $maintenanceWindow
     * @return list<int>
     */
    private function getOverlappingIncidentDurations(
        Collection $incidents,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?array $maintenanceWindow = null
    ): array {
        return $incidents
            ->map(function (Incident $incident) use ($periodStart, $periodEnd, $maintenanceWindow): int {
                $downAt = $incident->down_at->copy()->max($periodStart);
                $upAt = ($incident->up_at ?? $periodEnd)->copy()->min($periodEnd);
                $durationMinutes = max(0, (int) floor(($upAt->getTimestamp() - $downAt->getTimestamp()) / 60));

                if ($durationMinutes === 0 || ! $maintenanceWindow) {
                    return $durationMinutes;
                }

                $overlapStart = $downAt->copy()->max($maintenanceWindow['from']);
                $overlapEnd = $upAt->copy()->min($maintenanceWindow['until']);
                $maintenanceOverlapMinutes = $overlapStart->lt($overlapEnd)
                    ? max(0, (int) floor(($overlapEnd->getTimestamp() - $overlapStart->getTimestamp()) / 60))
                    : 0;

                return max(0, $durationMinutes - $maintenanceOverlapMinutes);
            })
            ->filter(static fn (int $durationMinutes): bool => $durationMinutes > 0)
            ->values()
            ->all();
    }

    private function buildAvailabilityFromLoadedDailyResults(
        Monitoring $monitoring,
        Carbon $periodStart,
        Carbon $periodEnd
    ): MonitoringAvailabilityPayload {
        $dailyResults = $monitoring->dailyResults;
        $trackingStartedAt = $dailyResults->min('date')?->copy()->startOfDay();

        if (! $trackingStartedAt || $trackingStartedAt->gt($periodEnd)) {
            return $this->buildAvailability($periodStart, $periodEnd, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        return $this->buildAvailability(
            $periodStart,
            $periodEnd,
            $trackingStartedAt,
            (int) $dailyResults->sum('uptime_minutes'),
            (int) $dailyResults->sum('downtime_minutes'),
            (int) $dailyResults->sum('unknown_minutes'),
            (int) $dailyResults->sum('uptime_total'),
            (int) $dailyResults->sum('downtime_total'),
            (int) $dailyResults->sum('unknown_total'),
            (int) $dailyResults->sum('incidents_count')
        );
    }

    private function buildAvailability(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?Carbon $trackingStartedAt,
        int $uptimeMinutes,
        int $downtimeMinutes,
        int $unknownMinutes,
        int $uptimeTotal,
        int $downtimeTotal,
        int $unknownTotal,
        int $incidentsCount
    ): MonitoringAvailabilityPayload {
        $totalTrackedMinutes = $uptimeMinutes + $downtimeMinutes + $unknownMinutes;
        $hasData = $totalTrackedMinutes > 0;

        return new MonitoringAvailabilityPayload(
            from: $periodStart,
            to: $periodEnd,
            hasData: $hasData,
            trackingStartedAt: $trackingStartedAt?->toIso8601String(),
            uptime: new MonitoringAvailabilitySegmentPayload(
                minutes: $uptimeMinutes,
                percentage: $hasData ? ($uptimeMinutes / $totalTrackedMinutes) * 100 : null,
                total: $uptimeTotal
            ),
            downtime: new MonitoringAvailabilitySegmentPayload(
                minutes: $downtimeMinutes,
                percentage: $hasData ? ($downtimeMinutes / $totalTrackedMinutes) * 100 : null,
                total: $downtimeTotal,
                incidentsCount: $incidentsCount
            ),
            unknown: new MonitoringAvailabilitySegmentPayload(
                minutes: $unknownMinutes,
                percentage: $hasData ? ($unknownMinutes / $totalTrackedMinutes) * 100 : null,
                total: $unknownTotal
            )
        );
    }

    /**
     * @return array{from: Carbon, until: Carbon}|null
     */
    private function getOverlappingMaintenanceWindow(Monitoring $monitoring, Carbon $periodStart, Carbon $periodEnd): ?array
    {
        if (! $monitoring->maintenance_from) {
            return null;
        }

        $maintenanceFrom = $monitoring->maintenance_from->copy();
        $maintenanceUntil = ($monitoring->maintenance_until ?? $periodEnd)->copy();

        if ($maintenanceUntil->lt($periodStart) || $maintenanceFrom->gt($periodEnd)) {
            return null;
        }

        $overlapFrom = $maintenanceFrom->max($periodStart);
        $overlapUntil = $maintenanceUntil->min($periodEnd);

        if ($overlapFrom->gt($overlapUntil)) {
            return null;
        }

        return [
            'from' => $overlapFrom,
            'until' => $overlapUntil,
        ];
    }

    /**
     * @param  array{from: Carbon, until: Carbon}|null  $maintenanceWindow
     */
    private function getMaintenanceMinutes(?array $maintenanceWindow): int
    {
        if (! $maintenanceWindow) {
            return 0;
        }

        return max(0, (int) ceil(
            ($maintenanceWindow['until']->getTimestamp() - $maintenanceWindow['from']->getTimestamp()) / 60
        ));
    }

    private function removeMaintenanceMinutes(
        int $maintenanceMinutes,
        int &$uptimeMinutes,
        int &$downtimeMinutes,
        int &$unknownMinutes
    ): void {
        if ($maintenanceMinutes <= 0) {
            return;
        }

        $removedUnknownMinutes = min($unknownMinutes, $maintenanceMinutes);
        $unknownMinutes -= $removedUnknownMinutes;
        $maintenanceMinutes -= $removedUnknownMinutes;

        $removedDowntimeMinutes = min($downtimeMinutes, $maintenanceMinutes);
        $downtimeMinutes -= $removedDowntimeMinutes;
        $maintenanceMinutes -= $removedDowntimeMinutes;

        $removedUptimeMinutes = min($uptimeMinutes, $maintenanceMinutes);
        $uptimeMinutes -= $removedUptimeMinutes;
    }

    private function expiresSoonOrIsInvalid(MonitoringSslResult|MonitoringDomainResult|null $result, Carbon $warningThreshold): bool
    {
        if (! $result) {
            return false;
        }

        if (! $result->is_valid) {
            return true;
        }

        return $result->expires_at !== null && $result->expires_at->lte($warningThreshold);
    }

    /**
     * @return array{name: string, target: string, expires_at: Carbon|null, is_valid: bool}
     */
    private function buildExpiryWarning(Monitoring $monitoring, MonitoringSslResult|MonitoringDomainResult $result): array
    {
        return [
            'name' => $monitoring->name,
            'target' => $monitoring->target,
            'expires_at' => $result->expires_at,
            'is_valid' => $result->is_valid,
        ];
    }
}
