<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringUptimeCalendarDayPayload;
use App\Data\MonitoringUptimeCalendarMonthPayload;
use App\Data\MonitoringUptimeCalendarPayload;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringUptimeCalendarService
{
    public function getGroupedByDateAndMonth(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $includeMonthsBeforeMonitoringCreation = false,
    ): MonitoringUptimeCalendarPayload
    {
        $startDate = $startDate->copy();
        $endDate = $endDate->copy();

        if ($endDate->isFuture()) {
            $endDate = Date::now()->endOfDay();
        }

        if ($startDate->diffInDays($endDate) > 366) {
            $startDate = $endDate->copy()->subYear();
        }

        $monitoringStartDate = $monitoring->created_at->copy()->startOfDay();
        if (! $includeMonthsBeforeMonitoringCreation && $startDate->isBefore($monitoringStartDate)) {
            $startDate = $monitoringStartDate;
        }

        $historicalData = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(['date', 'uptime_minutes', 'downtime_minutes'])
            ->get()
            ->keyBy(fn ($result) => Date::parse($result->date)->toDateString());

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
                $uptimePercentage = null;
                $uptimeMinutes = 0;
                $downtimeMinutes = 0;

                if ($currentDay->between($startDate, $endDate) && $historicalData->has($dateString)) {
                    $result = $historicalData[$dateString];
                    $uptimeMinutes = (int) ($result->uptime_minutes ?? 0);
                    $downtimeMinutes = (int) ($result->downtime_minutes ?? 0);
                    $totalTrackedMinutes = $uptimeMinutes + $downtimeMinutes;
                    $uptimePercentage = $totalTrackedMinutes > 0
                        ? ($uptimeMinutes / $totalTrackedMinutes) * 100
                        : null;
                }

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
}
