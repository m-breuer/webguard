<?php

namespace App\Services;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Class MonitoringResultService
 *
 * Provides services for calculating and retrieving monitoring results and statistics.
 */
class MonitoringResultService
{
    /**
     * Calculates a 24-hour heatmap of uptime/downtime statistics for a given monitoring instance.
     * The heatmap covers the specified date range, providing an hourly breakdown of status.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to generate the heatmap for.
     * @param  Carbon  $startDate  The start date and time for the heatmap.
     * @param  Carbon  $endDate  The end date and time for the heatmap.
     * @return Collection<int, array{date: string, uptime: int, downtime: int, unknown: int}> A collection representing the uptime heatmap.
     *
     * @example
     * [
     *   {
     *     "date": "2024-01-01 00::00:00",
     *     "uptime": 60,
     *     "downtime": 0,
     *     "unknown": 0
     *   },
     *   {
     *     "date": "2024-01-01 01:00:00",
     *     "uptime": 50,
     *     "downtime": 10,
     *     "unknown": 0
     *   }
     * ]
     */
    public static function getHeatmap(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): Collection
    {
        // Enforce 24-hour window for heatmap
        $startDate = Date::now()->subHours(23)->startOfHour();
        $endDate = Date::now()->endOfHour();

        $raw = self::getMonitoringResponseQuery($endDate)
            ->where('monitoring_id', $monitoring->id)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H') as period,
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as uptime,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as downtime,
                SUM(CASE WHEN status NOT IN ('up', 'down') THEN 1 ELSE 0 END) as unknown
            ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        return collect(
            CarbonPeriod::create($startDate, '1 hour', $endDate)
                ->map(function (Carbon $hour) use ($raw) {
                    $formatted = Carbon::parse($hour->format('Y-m-d H:00:00'))->toDateTimeString();

                    $record = $raw->get($formatted);

                    return [
                        'date' => $formatted,
                        'uptime' => (int) ($record->uptime ?? 0),
                        'downtime' => (int) ($record->downtime ?? 0),
                        'unknown' => (int) ($record->unknown ?? 0),
                    ];
                })
        );
    }

    /**
     * Calculates uptime/downtime percentages and totals for the given monitoring and date range.
     * Downtime is accurately calculated based on incident durations, while uptime is derived
     * from the total period minus downtime.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to calculate uptime for.
     * @param  Carbon  $startDate  The start date of the period.
     * @param  Carbon  $endDate  The end date of the period.
     * @param  bool  $loadAggregatedData  Whether to load aggregated data if available. Defaults to false.
     * @return Collection{
     *     data: array{from: Carbon, to: Carbon},
     *     uptime: array{total: int, percentage: float, percentage_rounded: string, total_human: string, total_minutes: int},
     *     downtime: array{total: int, percentage: float, percentage_rounded: string, total_human: string, total_minutes: int}
     * } A collection containing uptime and downtime statistics.
     *
     * @example
     * {
     *   "data": {
     *     "from": "2024-01-01 00:00:00",
     *     "to": "2024-01-07 23:59:59"
     *   },
     *   "uptime": {
     *     "total": 1000,
     *     "percentage": 99.99,
     *     "percentage_rounded": "99.99",
     *     "total_human": "7 days",
     *     "total_minutes": 10080
     *   },
     *   "downtime": {
     *     "total": 1,
     *     "percentage": 0.01,
     *     "percentage_rounded": "0.01",
     *     "total_human": "1 minute",
     *     "total_minutes": 1
     *   }
     * }
     */
    public static function getUptimeDowntime(Monitoring $monitoring, Carbon $startDate, Carbon $endDate, bool $loadAggregatedData = false): Collection
    {
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();

        if ($loadAggregatedData) {
            $aggregatedData = $monitoring->dailyResults()
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw('
                    SUM(uptime_total) as uptime_total,
                    SUM(downtime_total) as downtime_total,
                    SUM(uptime_minutes) as uptime_minutes,
                    SUM(downtime_minutes) as downtime_minutes
                ')
                ->first();

            $totalMinutes = ($aggregatedData->uptime_minutes ?? 0) + ($aggregatedData->downtime_minutes ?? 0);
            $uptimePercentage = $totalMinutes > 0 ? (($aggregatedData->uptime_minutes ?? 0) / $totalMinutes) * 100 : 0;
            $downtimePercentage = $totalMinutes > 0 ? (($aggregatedData->downtime_minutes ?? 0) / $totalMinutes) * 100 : 0;

            return collect([
                'data' => [
                    'from' => $startDate,
                    'to' => $endDate,
                ],
                'uptime' => [
                    'total' => $aggregatedData->uptime_total ?? 0,
                    'percentage' => $uptimePercentage,
                    'percentage_rounded' => $uptimePercentage,
                    'total_human' => $aggregatedData->uptime_minutes ?? 0,
                    'total_minutes' => $aggregatedData->uptime_minutes ?? 0,
                ],
                'downtime' => [
                    'total' => $aggregatedData->downtime_total ?? 0,
                    'percentage' => $downtimePercentage,
                    'percentage_rounded' => $downtimePercentage,
                    'total_human' => $aggregatedData->downtime_minutes ?? 0,
                    'total_minutes' => $aggregatedData->downtime_minutes ?? 0,
                ],
            ]);
        }

        // Calculate total minutes in the period.
        $totalMinutesInPeriod = $startDate->diffInMinutes($endDate);

        // Get total downtime minutes from incidents that were active during the period.
        $boundStartDate = $startDate->toDateTimeString();
        $boundEndDate = $endDate->toDateTimeString();

        $totalDowntimeMinutes = $monitoring->incidents()
            ->where('down_at', '<=', $endDate)
            ->where(function (Builder $builder) use ($startDate) {
                $builder->where('up_at', '>=', $startDate)
                    ->orWhereNull('up_at');
            })
            ->sum(DB::raw("GREATEST(0, TIMESTAMPDIFF(MINUTE, GREATEST(down_at, '{$boundStartDate}'), LEAST(COALESCE(up_at, '{$boundEndDate}'), '{$boundEndDate}')))"));

        // Ensure downtime doesn't exceed the total period.
        $overallDowntimeMinutes = min($totalDowntimeMinutes, $totalMinutesInPeriod);

        // Calculate uptime minutes.
        $overallUptimeMinutes = $totalMinutesInPeriod - $overallDowntimeMinutes;

        // Ensure uptime is not negative.
        $overallUptimeMinutes = max(0, $overallUptimeMinutes);

        // Calculate percentages based on minutes.
        $overallUptimePercentage = $totalMinutesInPeriod > 0 ? ($overallUptimeMinutes / $totalMinutesInPeriod) * 100 : 100;
        $overallDowntimePercentage = $totalMinutesInPeriod > 0 ? ($overallDowntimeMinutes / $totalMinutesInPeriod) * 100 : 0;

        // For 'total' (count of checks), we still need to query monitoring_responses.
        // This is separate from the duration calculation.
        $data = self::getMonitoringResponseQuery($endDate)
            ->where('monitoring_id', $monitoring->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as uptime_total,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as downtime_total
            ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->first();

        return collect([
            'data' => [
                'from' => $startDate,
                'to' => $endDate,
            ],
            'uptime' => [
                'total' => $data->uptime_total ?? 0,
                'percentage' => $overallUptimePercentage,
                'percentage_rounded' => $overallUptimePercentage,
                'total_human' => $overallUptimeMinutes,
                'total_minutes' => $overallUptimeMinutes,
            ],
            'downtime' => [
                'total' => $data->downtime_total ?? 0,
                'percentage' => $overallDowntimePercentage,
                'percentage_rounded' => $overallDowntimePercentage,
                'total_human' => $overallDowntimeMinutes,
                'total_minutes' => $overallDowntimeMinutes,
            ],
        ]);
    }

    /**
     * Determine the time since the current monitoring status (up/down) began.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to check the status for.
     * @return array{status: string, since: string} An array containing the status and time since last change.
     *
     * @example
     * {
     *   "status": "up",
     *   "since": "2024-01-01T12:00:00Z"
     * }
     */
    public static function getStatusSince(Monitoring $monitoring): array
    {
        $latest = $monitoring->latestIncident;

        if (! $latest) {
            return [
                'status' => $monitoring->latestResponseResult ? $monitoring->latestResponseResult->status->value : MonitoringStatus::UNKNOWN->value,
                'since' => $monitoring->latestResponseResult ? $monitoring->created_at->toIso8601String() : null,
            ];
        }

        if ($latest->up_at) {
            return [
                'status' => MonitoringStatus::UP->value,
                'since' => $latest->up_at->toIso8601String(),
            ];
        }

        return [
            'status' => MonitoringStatus::DOWN->value,
            'since' => $latest->down_at->toIso8601String(),
        ];
    }

    /**
     * Returns the current Status and the next check.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to check the status for.
     * @param  int  $cronjobInterval  The interval in seconds for the cron job.
     * @return array{status: string, checked_at: string, next: string, interval: int} An array containing the status, last checked time and next check time.
     *
     * @example
     * {
     *   "status": "up",
     *   "checked_at": "2024-01-01T12:00:00Z",
     *   "next": "2024-01-01T12:01:00Z",
     *   "interval": 60
     * }
     */
    public static function getStatusNow(Monitoring $monitoring, int $cronjobInterval = 60): array
    {
        $latest = $monitoring->latestResponseResult;

        return [
            'status' => $latest ? $latest->status : MonitoringStatus::UNKNOWN->value,
            'checked_at' => $latest ? $latest->updated_at->toIso8601String() : null,
            'next' => $latest ? $latest->updated_at->addSeconds($cronjobInterval)->toIso8601String() : Carbon::now()->addSeconds($cronjobInterval)->toIso8601String(),
            'interval' => $cronjobInterval,
        ];
    }

    /**
     * Calculates response time statistics for a given monitoring and date range.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to calculate response times for.
     * @param  Carbon  $startDate  The start date of the period.
     * @param  Carbon  $endDate  The end date of the period.
     * @param  bool  $loadAggregatedData  Whether to load aggregated data if available. Defaults to false.
     * @return Collection{
     *     data: Collection<int, array{date: string, avg: float, min: float, max: float}>,
     *     aggregated: array{avg: float, min: float, max: float}
     * } A collection containing response time statistics.
     *
     * @remarks When `loadAggregatedData` is true, the `data` key will contain a collection of daily aggregated response times,
     *          and the `aggregated` key will contain the overall aggregated response times for the entire period.
     *
     * @example
     * For raw data (e.g., $days = 1):
     * {
     *   "data": [
     *     {
     *       "date": "2024-01-01 10:00:00",
     *       "avg": 150.5,
     *       "min": 100,
     *       "max": 200
     *     }
     *   ],
     *   "aggregated": {
     *     "avg": 150.5,
     *     "min": 100,
     *     "max": 200
     *   }
     * }
     * For aggregated data (e.g., $days > 1 and $loadAggregatedData = true):
     *   "data": [ // Collection of daily aggregated response times
     *     {
     *       "date": "2024-01-01 00:00:00",
     *       "avg": 150.5,
     *       "min": 100,
     *       "max": 200
     *     }
     *   ],
     *   "aggregated": {
     *     "avg": 150.5,
     *     "min": 100,
     *     "max": 200
     *   }
     * }
     */
    public static function getResponseTimes(Monitoring $monitoring, Carbon $startDate, Carbon $endDate, bool $loadAggregatedData = false): Collection
    {
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();

        if ($loadAggregatedData) {
            $dailyAggregatedData = $monitoring->dailyResults()
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('date')
                ->get();

            $combinedData = $dailyAggregatedData->map(function ($row) {
                return [
                    'date' => Date::parse($row->date)->toIso8601String(),
                    'avg' => $row->avg_response_time ?? 0,
                    'min' => $row->min_response_time ?? 0,
                    'max' => $row->max_response_time ?? 0,
                ];
            });

            return collect([
                'data' => $combinedData,
                'aggregated' => [
                    'avg' => $combinedData->avg('avg'),
                    'min' => $combinedData->min('min'),
                    'max' => $combinedData->max('max'),
                ],
            ]);
        }

        $grouping = self::getGrouping(Date::parse($startDate)->diffInDays($endDate));

        $data = self::getMonitoringResponseQuery($endDate)
            ->where('monitoring_id', $monitoring->id)
            ->selectRaw("DATE_FORMAT(created_at, '{$grouping}') as period,
                    AVG(response_time) as avg_response_time,
                    MIN(response_time) as min_response_time,
                    MAX(response_time) as max_response_time
                ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('response_time')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Combine and process data for final output.
        $combinedData = $data->map(function ($row) {
            return [
                'date' => Date::parse($row['period'] . ':00:00')->toIso8601String(),
                'avg' => $row['avg_response_time'],
                'min' => $row['min_response_time'],
                'max' => $row['max_response_time'],
            ];
        });

        return collect([
            'data' => $combinedData,
            'aggregated' => [
                'avg' => $combinedData->avg('avg'),
                'min' => $combinedData->min('min'),
                'max' => $combinedData->max('max'),
            ],
        ]);
    }

    /**
     * Returns all incidents within a date range (status DOWN followed by UP).
     * Incidents are retrieved directly from the database and are not aggregated.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to retrieve incidents for.
     * @param  Carbon  $startDate  The start date of the period.
     * @param  Carbon  $endDate  The end date of the period.
     * @return Collection<int, array{down_at: string, up_at: string|null, duration: string}> A collection of incidents.
     *
     * @example
     * [
     *   {
     *     "down_at": "01.01.2024 10:00:00",
     *     "up_at": "01.01.2024 10:15:00",
     *     "duration": "15 minutes"
     *   },
     *   {
     *     "down_at": "01.01.2024 08:00:00",
     *     "up_at": null,
     *     "duration": "2 hours"
     *   }
     * ]
     */
    public static function getIncidents(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): Collection
    {
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();

        // Retrieve incidents directly from the database within the specified date range.
        $incidents = $monitoring->incidents()
            ->whereBetween('down_at', [$startDate, $endDate])
            ->select(
                'down_at',
                'up_at',
                DB::raw('TIMESTAMPDIFF(MINUTE, down_at, COALESCE(up_at, NOW())) as duration_minutes')
            )
            ->latest('down_at')
            ->get();

        // Map incidents to the desired output format.
        return $incidents->map(function ($incident) {
            $downAt = Date::parse($incident->down_at);
            $upAt = $incident->up_at ? Date::parse($incident->up_at) : null;

            return [
                'down_at' => $downAt->toIso8601String(),
                'up_at' => $upAt?->toIso8601String(),
                'duration' => max(1, (int) ceil($incident->duration_minutes)),
            ];
        });
    }

    /**
     * Get daily uptime data for a calendar view for a given monitoring.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @param  Carbon  $startDate  The start date for the calendar view.
     * @param  Carbon  $endDate  The end date for the calendar view.
     * @return array An array of daily uptime data, grouped by month.
     *
     * @example
     * [
     *   "2024-07" => [
     *     [
     *       "date" => "2024-07-01 00:00:00",
     *       "uptime_percentage" => 99.98
     *     ],
     *     [
     *       "date" => "2024-07-02 00:00:00",
     *       "uptime_percentage" => 100.00
     *     ]
     *   ],
     *   "2024-08" => [
     *     [
     *       "date" => "2024-08-01 00:00:00",
     *       "uptime_percentage" => null
     *     ]
     *   ]
     * ]
     */
    public static function getUpTimeGroupByDateAndMonth(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): array
    {
        if ($endDate->isFuture()) {
            $endDate = Date::now()->endOfDay();
        }

        if ($startDate->diffInDays($endDate) > 366) {
            $startDate = $endDate->copy()->subYear();
        }

        $monitoringStartDate = $monitoring->created_at->copy()->startOfDay();
        if ($startDate->isBefore($monitoringStartDate)) {
            $startDate = $monitoringStartDate;
        }

        $dailyUptimeData = [];
        $currentDate = Date::today();

        $historicalData = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn($result) => Date::parse($result->date)->toDateString());

        $carbonPeriod = CarbonPeriod::create($startDate->copy()->startOfMonth(), '1 month', $endDate->copy()->endOfMonth());

        foreach ($carbonPeriod as $monthDate) {
            $monthYear = $monthDate->format('Y-m');
            $daysInMonth = $monthDate->daysInMonth;
            $monthDays = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDay = $monthDate->copy()->setDay($day)->startOfDay();
                $dateString = $currentDay->toDateString();
                $uptimePercentage = null;

                if ($currentDay->between($startDate, $endDate)) {
                    if ($currentDay->lt($currentDate)) {
                        if ($historicalData->has($dateString)) {
                            $uptimePercentage = $historicalData[$dateString]->uptime_percentage;
                        }
                    } elseif ($currentDay->eq($currentDate)) {
                        $currentDayUptimeData = self::getUptimeDowntime($monitoring, $currentDay, $currentDay->copy()->endOfDay(), false);
                        if ($currentDayUptimeData['uptime']['total'] === 0 && $currentDayUptimeData['downtime']['total'] === 0) {
                            $uptimePercentage = null;
                        } else {
                            $uptimePercentage = $currentDayUptimeData['uptime']['percentage'];
                        }
                    }
                }

                $monthDays[] = [
                    'date' => $currentDay->toIso8601String(),
                    'uptime_percentage' => $uptimePercentage,
                ];
            }
            $dailyUptimeData[$monthYear] = $monthDays;
        }

        $filteredAndAggregatedData = [];
        foreach ($dailyUptimeData as $monthYear => $days) {
            $validUptimes = array_filter(array_column($days, 'uptime_percentage'), fn($value) => $value !== null);

            if (! empty($validUptimes)) {
                $monthStartDate = Date::createFromFormat('Y-m', $monthYear)->startOfMonth();
                $monthEndDate = Date::createFromFormat('Y-m', $monthYear)->endOfMonth();

                $calculationStartDate = $monthStartDate->max($startDate);
                $calculationEndDate = $monthEndDate->min($endDate);

                $isPastMonth = $calculationEndDate->isPast();

                $uptimeData = self::getUptimeDowntime($monitoring, $calculationStartDate, $calculationEndDate->copy()->endOfDay(), $isPastMonth);
                $monthlyAverage = $uptimeData['uptime']['percentage'];

                $filteredAndAggregatedData[$monthYear] = [
                    'days' => $days,
                    'monthly_average_uptime' => $monthlyAverage,
                ];
            } else {
                $filteredAndAggregatedData[$monthYear] = [
                    'days' => $days,
                    'monthly_average_uptime' => null,
                ];
            }
        }

        return $filteredAndAggregatedData;
    }

    /**
     * Get the grouping format for date-based queries based on the number of days in the period.
     *
     * @param  int  $days  The number of days in the period.
     * @return string The date format string for grouping (e.g., '%Y-%m-%d %H' for hourly, '%Y-%m-%d' for daily).
     */
    private static function getMonitoringResponseQuery(Carbon $endDate)
    {
        // If the end date is older than 7 days, query the archived responses.
        if ($endDate->lt(Date::now()->subWeek()->startOfDay())) {
            return MonitoringResponseArchived::query();
        }

        // Otherwise, query the live monitoring responses.
        return MonitoringResponse::query();
    }

    private static function getGrouping(int $days): string
    {
        return match (true) {
            $days <= 1 => '%Y-%m-%d %H',
            $days <= 30 => '%Y-%m-%d',
            default => '%Y-%m',
        };
    }
}
