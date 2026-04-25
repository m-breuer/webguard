<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringSslResult;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

class WeeklyMonitoringDigestService
{
    /**
     * @return array{
     *     period_start: Carbon,
     *     period_end: Carbon,
     *     overview: array{uptime_percentage: float|null, incidents_count: int, longest_downtime_minutes: int, monitorings_count: int},
     *     monitorings: list<array{name: string, target: string, uptime_percentage: float|null, incidents_count: int, downtime_minutes: int, longest_downtime_minutes: int}>,
     *     ssl_warnings: list<array{name: string, target: string, expires_at: Carbon|null, is_valid: bool}>,
     *     domain_warnings: list<array{name: string, target: string, expires_at: Carbon|null, is_valid: bool}>
     * }
     */
    public function buildForUser(User $user, ?Carbon $periodEnd = null): array
    {
        $periodEnd = ($periodEnd ?? Date::now()->subDay())->copy()->endOfDay();
        $periodStart = $periodEnd->copy()->subDays(6)->startOfDay();

        $monitorings = $user->monitorings()
            ->active()
            ->with(['sslResult', 'domainResult'])
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
        $warningThreshold = Date::now()->addDays((int) config('monitoring.digest_expiry_warning_days', 30))->endOfDay();

        foreach ($monitorings as $monitoring) {
            $uptimeDowntime = MonitoringResultService::getUptimeDowntime(
                $monitoring,
                $periodStart,
                $periodEnd,
                true,
                false
            );

            $incidentDurations = $this->getOverlappingIncidentDurations($monitoring, $periodStart, $periodEnd);
            $incidentsCount = count($incidentDurations);
            $monitoringLongestDowntimeMinutes = empty($incidentDurations) ? 0 : max($incidentDurations);

            $uptimeMinutes = (int) ($uptimeDowntime['uptime']['minutes'] ?? 0);
            $downtimeMinutes = (int) ($uptimeDowntime['downtime']['minutes'] ?? 0);
            $unknownMinutes = (int) ($uptimeDowntime['unknown']['minutes'] ?? 0);

            $totalUptimeMinutes += $uptimeMinutes;
            $totalDowntimeMinutes += $downtimeMinutes;
            $totalUnknownMinutes += $unknownMinutes;
            $totalIncidents += $incidentsCount;
            $longestDowntimeMinutes = max($longestDowntimeMinutes, $monitoringLongestDowntimeMinutes);

            $monitoringRows[] = [
                'name' => $monitoring->name,
                'target' => $monitoring->target,
                'uptime_percentage' => $uptimeDowntime['uptime']['percentage'] ?? null,
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
     * @return list<int>
     */
    private function getOverlappingIncidentDurations(Monitoring $monitoring, Carbon $periodStart, Carbon $periodEnd): array
    {
        return $monitoring->incidents()
            ->where('down_at', '<=', $periodEnd)
            ->where(function ($builder) use ($periodStart): void {
                $builder->where('up_at', '>=', $periodStart)
                    ->orWhereNull('up_at');
            })
            ->get(['down_at', 'up_at'])
            ->map(function (Incident $incident) use ($periodStart, $periodEnd): int {
                $downAt = $incident->down_at->copy()->max($periodStart);
                $upAt = ($incident->up_at ?? $periodEnd)->copy()->min($periodEnd);

                return max(0, (int) floor(($upAt->getTimestamp() - $downAt->getTimestamp()) / 60));
            })
            ->values()
            ->all();
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
