<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringUptimeCalendarDayPayload;
use App\Data\MonitoringUptimeCalendarMonthPayload;
use App\Data\MonitoringUptimeCalendarPayload;
use App\Enums\StatusPageComponentSource;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class StatusPageUptimeCalendarService
{
    public const LOOKBACK_DAYS = 30;

    public function getLast30Days(StatusPage $statusPage): MonitoringUptimeCalendarPayload
    {
        $statusPage->loadMissing([
            'components.monitorings' => fn ($query) => $query->withoutGlobalScope('user'),
            'components.monitoringGroup.monitorings' => fn ($query) => $query->withoutGlobalScope('user'),
        ]);

        $monitoringIds = $statusPage->components
            ->flatMap(fn (StatusPageComponent $statusPageComponent): Collection => $this->componentMonitorings($statusPageComponent)->pluck('id'))
            ->unique()
            ->values();

        $endDate = Date::now()->endOfDay();
        $startDate = $endDate->copy()->subDays(self::LOOKBACK_DAYS - 1)->startOfDay();

        $historicalData = MonitoringDailyResult::query()
            ->whereIn('monitoring_id', $monitoringIds->all())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(['monitoring_id', 'date', 'uptime_minutes', 'downtime_minutes'])
            ->get()
            ->groupBy(fn (MonitoringDailyResult $monitoringDailyResult): string => Date::parse($monitoringDailyResult->date)->toDateString());

        $dailyUptimeData = [];
        $monthlyMinutes = [];
        $carbonPeriod = CarbonPeriod::create($startDate->copy()->startOfMonth(), '1 month', $endDate->copy()->endOfMonth());

        foreach ($carbonPeriod as $monthDate) {
            $monthYear = $monthDate->format('Y-m');
            $monthDays = [];
            $monthlyMinutes[$monthYear] = [
                'uptime_minutes' => 0,
                'downtime_minutes' => 0,
            ];

            for ($day = 1; $day <= $monthDate->daysInMonth; $day++) {
                $currentDay = $monthDate->copy()->setDay($day)->startOfDay();
                $dateString = $currentDay->toDateString();
                $uptimeMinutes = 0;
                $downtimeMinutes = 0;

                if ($currentDay->between($startDate, $endDate)) {
                    $results = $historicalData->get($dateString, collect());
                    $uptimeMinutes = (int) $results->sum('uptime_minutes');
                    $downtimeMinutes = (int) $results->sum('downtime_minutes');
                }

                $trackedMinutes = $uptimeMinutes + $downtimeMinutes;
                $uptimePercentage = $trackedMinutes > 0
                    ? ($uptimeMinutes / $trackedMinutes) * 100
                    : null;

                $monthlyMinutes[$monthYear]['uptime_minutes'] += $uptimeMinutes;
                $monthlyMinutes[$monthYear]['downtime_minutes'] += $downtimeMinutes;

                $monthDays[] = new MonitoringUptimeCalendarDayPayload(
                    date: $currentDay->toIso8601String(),
                    uptimePercentage: $uptimePercentage
                );
            }

            $dailyUptimeData[$monthYear] = collect($monthDays);
        }

        $months = collect($dailyUptimeData)
            ->mapWithKeys(function (Collection $days, string $monthYear) use ($monthlyMinutes): array {
                $uptimeMinutes = $monthlyMinutes[$monthYear]['uptime_minutes'] ?? 0;
                $downtimeMinutes = $monthlyMinutes[$monthYear]['downtime_minutes'] ?? 0;
                $totalTrackedMinutes = $uptimeMinutes + $downtimeMinutes;

                return [
                    $monthYear => new MonitoringUptimeCalendarMonthPayload(
                        days: $days,
                        monthlyAverageUptime: $totalTrackedMinutes > 0
                            ? ($uptimeMinutes / $totalTrackedMinutes) * 100
                            : null
                    ),
                ];
            });

        return new MonitoringUptimeCalendarPayload($months);
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
}
