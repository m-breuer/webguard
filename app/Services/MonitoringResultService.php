<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use Carbon\Carbon;
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
        $heatmaps = self::getHeatmapsForMonitorings(collect([$monitoring]), $startDate, $endDate);

        return collect($heatmaps[$monitoring->id] ?? []);
    }

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     * @return array<string, list<array{date: Carbon, uptime: int, downtime: int, unknown: int}>>
     */
    public static function getHeatmapsForMonitorings(Collection $monitorings, Carbon $startDate, Carbon $endDate): array
    {
        if ($monitorings->isEmpty()) {
            return [];
        }

        // Enforce 24-hour window for heatmap payloads.
        $startDate = Date::now()->subHours(23)->startOfHour();
        $endDate = Date::now()->endOfHour();

        $monitoringIds = $monitorings
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values();

        $interval = (int) config('monitoring.interval', 5);
        $periodExpression = self::getPeriodExpression('created_at', '%Y-%m-%d %H');

        $rawByMonitoring = self::getMonitoringResponseQuery($endDate)
            ->whereIn('monitoring_id', $monitoringIds)
            ->selectRaw("monitoring_id, {$periodExpression} as period,
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) * {$interval} as uptime,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) * {$interval} as downtime,
                SUM(CASE WHEN status NOT IN ('up', 'down') THEN 1 ELSE 0 END) * {$interval} as unknown
            ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('monitoring_id', 'period')
            ->orderBy('period')
            ->get()
            ->groupBy('monitoring_id')
            ->map(static fn (Collection $rows): Collection => $rows->keyBy('period'));

        return $monitoringIds
            ->mapWithKeys(function (string $monitoringId) use ($rawByMonitoring, $startDate, $endDate): array {
                /** @var Collection<int, object> $raw */
                $raw = $rawByMonitoring->get($monitoringId, collect());

                $heatmap = collect(CarbonPeriod::create($startDate, '1 hour', $endDate))
                    ->map(function (Carbon $hour) use ($raw): array {
                        $record = $raw->get($hour->format('Y-m-d H'));

                        return [
                            'date' => $hour,
                            'uptime' => (int) ($record->uptime ?? 0),
                            'downtime' => (int) ($record->downtime ?? 0),
                            'unknown' => (int) ($record->unknown ?? 0),
                        ];
                    })
                    ->values()
                    ->all();

                return [$monitoringId => $heatmap];
            })
            ->all();
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
     * @param  bool  $includeIntradayRawData  Whether to include raw intraday data when using aggregated mode.
     * @return Collection{
     *     data: array{from: Carbon, to: Carbon},
     *     uptime: array{minutes: int, percentage: float, total: int},
     *     downtime: array{minutes: int, percentage: float, total: int},
     *     unknown: array{minutes: int, percentage: float, total: int}
     * } A collection containing uptime and downtime statistics.
     *
     * @example
     * {
     *   "data": {
     *     "from": "2024-01-01 00:00:00",
     *     "to": "2024-01-07 23:59:59"
     *   },
     *   "uptime": {
     *     "minutes": 10080,
     *     "percentage": 99.99
     *   },
     *   "downtime": {
     *     "minutes": 1,
     *     "percentage": 0.01
     *   }
     * }
     */
    public static function getUptimeDowntime(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $loadAggregatedData = false,
        bool $includeIntradayRawData = true
    ): Collection {
        $startDate = $startDate->copy();
        $endDate = $endDate->copy();

        if ($endDate->isFuture()) {
            $endDate = Date::now();
        }

        if ($startDate->gt($endDate)) {
            return self::buildUptimeDowntimeStats($startDate, $endDate, null, 0, 0, 0, 0, 0, 0, 0);
        }

        if ($loadAggregatedData) {
            return self::getAggregatedUptimeDowntime($monitoring, $startDate, $endDate, $includeIntradayRawData);
        }

        return self::getRawUptimeDowntime($monitoring, $startDate, $endDate);
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
     *   "interval": 300
     * }
     */
    public static function getStatusNow(Monitoring $monitoring, ?int $cronjobInterval = null): array
    {
        $cronjobInterval ??= (int) config('monitoring.interval', 5) * 60;
        $latest = $monitoring->latestResponseResult;

        return [
            'status' => $latest ? $latest->status : MonitoringStatus::UNKNOWN->value,
            'checked_at' => $latest ? $latest->updated_at->toIso8601String() : null,
            'next' => $latest ? $latest->updated_at->addSeconds($cronjobInterval)->toIso8601String() : Date::now()->addSeconds($cronjobInterval)->toIso8601String(),
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

        $grouping = self::getGrouping((int) Date::parse($startDate)->diffInDays($endDate));
        $periodExpression = self::getPeriodExpression('created_at', $grouping);

        $data = self::getMonitoringResponseQuery($endDate)
            ->where('monitoring_id', $monitoring->id)
            ->selectRaw("{$periodExpression} as period,
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
     *     "duration": "5 minutes"
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
                'up_at'
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
            ];
        });
    }

    /**
     * Returns incident count in a date range.
     */
    public static function countIncidents(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): int
    {
        return (int) $monitoring->incidents()
            ->whereBetween('down_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->count();
    }

    /**
     * Returns incident count for aggregated ranges and optionally adds intraday raw incidents.
     */
    public static function getAggregatedIncidentsCount(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $includeIntradayRawData = true
    ): int {
        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->endOfDay();

        if ($endDate->isFuture()) {
            $endDate = Date::now();
        }

        if ($startDate->gt($endDate)) {
            return 0;
        }

        $today = Date::today();
        $historicalEndDate = $includeIntradayRawData
            ? $endDate->copy()->min($today->copy()->subDay()->endOfDay())
            : $endDate->copy();

        $incidentsCount = 0;

        if ($startDate->lte($historicalEndDate)) {
            $incidentsCount += (int) $monitoring->dailyResults()
                ->whereBetween('date', [$startDate->toDateString(), $historicalEndDate->toDateString()])
                ->sum('incidents_count');
        }

        if (! $includeIntradayRawData || $endDate->lt($today)) {
            return $incidentsCount;
        }

        $liveStartDate = $startDate->copy()->max($today);

        if ($liveStartDate->gt($endDate)) {
            return $incidentsCount;
        }

        return $incidentsCount + self::countIncidents($monitoring, $liveStartDate, $endDate);
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

        $historicalData = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(['date', 'uptime_percentage', 'uptime_minutes', 'downtime_minutes'])
            ->get()
            ->keyBy(fn ($result) => Date::parse($result->date)->toDateString());

        $carbonPeriod = CarbonPeriod::create($startDate->copy()->startOfMonth(), '1 month', $endDate->copy()->endOfMonth());

        $monthlyMinutes = [];
        foreach ($carbonPeriod as $monthDate) {
            $monthYear = $monthDate->format('Y-m');
            $daysInMonth = $monthDate->daysInMonth;
            $monthDays = [];
            $monthlyMinutes[$monthYear] = [
                'uptime_minutes' => 0,
                'downtime_minutes' => 0,
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDay = $monthDate->copy()->setDay($day)->startOfDay();
                $dateString = $currentDay->toDateString();
                $uptimePercentage = null;
                $uptimeMinutes = 0;
                $downtimeMinutes = 0;

                if ($currentDay->between($startDate, $endDate) && $historicalData->has($dateString)) {
                    $result = $historicalData[$dateString];
                    $uptimePercentage = $result->uptime_percentage;
                    $uptimeMinutes = (int) ($result->uptime_minutes ?? 0);
                    $downtimeMinutes = (int) ($result->downtime_minutes ?? 0);
                }

                $monthlyMinutes[$monthYear]['uptime_minutes'] += $uptimeMinutes;
                $monthlyMinutes[$monthYear]['downtime_minutes'] += $downtimeMinutes;

                $monthDays[] = [
                    'date' => $currentDay->toIso8601String(),
                    'uptime_percentage' => $uptimePercentage,
                ];
            }
            $dailyUptimeData[$monthYear] = $monthDays;
        }

        $filteredAndAggregatedData = [];
        foreach ($dailyUptimeData as $monthYear => $days) {
            $uptimeMinutes = $monthlyMinutes[$monthYear]['uptime_minutes'] ?? 0;
            $downtimeMinutes = $monthlyMinutes[$monthYear]['downtime_minutes'] ?? 0;
            $totalTrackedMinutes = $uptimeMinutes + $downtimeMinutes;
            $monthlyAverage = $totalTrackedMinutes > 0 ? ($uptimeMinutes / $totalTrackedMinutes) * 100 : null;

            $filteredAndAggregatedData[$monthYear] = [
                'days' => $days,
                'monthly_average_uptime' => $monthlyAverage,
            ];
        }

        return $filteredAndAggregatedData;
    }

    private static function getAggregatedUptimeDowntime(
        Monitoring $monitoring,
        Carbon $startDate,
        Carbon $endDate,
        bool $includeIntradayRawData = true
    ): Collection {
        $trackingStartedAt = $includeIntradayRawData
            ? self::getTrackingStartedAt($monitoring)
            : self::getTrackingStartedAtFromDailyResults($monitoring);

        if (! $trackingStartedAt || $trackingStartedAt->gt($endDate)) {
            return self::buildUptimeDowntimeStats($startDate, $endDate, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        $uptimeMinutes = 0;
        $downtimeMinutes = 0;
        $unknownMinutes = 0;
        $uptimeTotal = 0;
        $downtimeTotal = 0;
        $unknownTotal = 0;
        $incidentsCount = 0;

        $today = Date::today();
        $historicalEndDate = $includeIntradayRawData
            ? $endDate->copy()->min($today->copy()->subDay()->endOfDay())
            : $endDate->copy();

        if ($startDate->lte($historicalEndDate)) {
            $aggregatedData = $monitoring->dailyResults()
                ->whereBetween('date', [$startDate->toDateString(), $historicalEndDate->toDateString()])
                ->selectRaw('
                    SUM(uptime_minutes) as uptime_minutes,
                    SUM(downtime_minutes) as downtime_minutes,
                    SUM(unknown_minutes) as unknown_minutes,
                    SUM(uptime_total) as uptime_total,
                    SUM(downtime_total) as downtime_total,
                    SUM(unknown_total) as unknown_total,
                    SUM(incidents_count) as incidents_count
                ')
                ->first();

            $uptimeMinutes += (int) ($aggregatedData->uptime_minutes ?? 0);
            $downtimeMinutes += (int) ($aggregatedData->downtime_minutes ?? 0);
            $unknownMinutes += (int) ($aggregatedData->unknown_minutes ?? 0);
            $uptimeTotal += (int) ($aggregatedData->uptime_total ?? 0);
            $downtimeTotal += (int) ($aggregatedData->downtime_total ?? 0);
            $unknownTotal += (int) ($aggregatedData->unknown_total ?? 0);
            $incidentsCount += (int) ($aggregatedData->incidents_count ?? 0);
        }

        if ($includeIntradayRawData && $endDate->gte($today)) {
            $liveStartDate = $startDate->copy()->max($today);
            $liveUptimeDowntime = self::getRawUptimeDowntime($monitoring, $liveStartDate, $endDate);

            $uptimeMinutes += (int) ($liveUptimeDowntime['uptime']['minutes'] ?? 0);
            $downtimeMinutes += (int) ($liveUptimeDowntime['downtime']['minutes'] ?? 0);
            $unknownMinutes += (int) ($liveUptimeDowntime['unknown']['minutes'] ?? 0);
            $uptimeTotal += (int) ($liveUptimeDowntime['uptime']['total'] ?? 0);
            $downtimeTotal += (int) ($liveUptimeDowntime['downtime']['total'] ?? 0);
            $unknownTotal += (int) ($liveUptimeDowntime['unknown']['total'] ?? 0);
            $incidentsCount += (int) ($liveUptimeDowntime['downtime']['incidents_count'] ?? 0);
        }

        return self::buildUptimeDowntimeStats(
            $startDate,
            $endDate,
            $trackingStartedAt,
            $uptimeMinutes,
            $downtimeMinutes,
            $unknownMinutes,
            $uptimeTotal,
            $downtimeTotal,
            $unknownTotal,
            $incidentsCount
        );
    }

    private static function getRawUptimeDowntime(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): Collection
    {
        $trackingStartedAt = self::getTrackingStartedAt($monitoring);

        if (! $trackingStartedAt || $trackingStartedAt->gt($endDate)) {
            return self::buildUptimeDowntimeStats($startDate, $endDate, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        $effectiveStartDate = $startDate->copy()->max($trackingStartedAt);
        $effectiveEndDate = $endDate->copy();

        // A single result at the boundary does not define a measurable uptime window yet.
        if ($effectiveStartDate->gte($effectiveEndDate)) {
            return self::buildUptimeDowntimeStats($startDate, $endDate, $trackingStartedAt, 0, 0, 0, 0, 0, 0, 0);
        }

        $builder = self::getMonitoringResponseQuery($endDate)
            ->where('monitoring_id', $monitoring->id);

        $statusAtStart = (clone $builder)
            ->where('created_at', '<=', $effectiveStartDate)->latest()
            ->orderByDesc('id')
            ->value('status');

        $responses = (clone $builder)
            ->select(['id', 'status', 'created_at'])
            ->whereBetween('created_at', [$effectiveStartDate, $effectiveEndDate])->oldest()
            ->orderBy('id')
            ->get();

        $overallUptimeMinutes = 0;
        $overallDowntimeMinutes = 0;
        $overallUnknownMinutes = 0;
        $cursor = $effectiveStartDate->copy();
        $currentStatus = $statusAtStart;

        foreach ($responses as $response) {
            $responseTimestamp = Date::parse($response->created_at);

            if ($responseTimestamp->gt($cursor)) {
                $segmentMinutes = (int) $cursor->diffInMinutes($responseTimestamp);
                self::incrementMinutesByStatus(
                    $currentStatus,
                    $segmentMinutes,
                    $overallUptimeMinutes,
                    $overallDowntimeMinutes,
                    $overallUnknownMinutes
                );

                $cursor = $responseTimestamp;
            }

            $currentStatus = $response->status instanceof MonitoringStatus
                ? $response->status->value
                : (string) $response->status;
        }

        if ($cursor->lt($effectiveEndDate)) {
            $segmentMinutes = (int) $cursor->diffInMinutes($effectiveEndDate);
            self::incrementMinutesByStatus(
                $currentStatus,
                $segmentMinutes,
                $overallUptimeMinutes,
                $overallDowntimeMinutes,
                $overallUnknownMinutes
            );
        }

        // Calculate percentages based on minutes.
        // For 'total' (count of checks), we still need to query monitoring_responses.
        // This is separate from the duration calculation.
        $data = (clone $builder)
            ->selectRaw("
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as uptime_total,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as downtime_total,
                SUM(CASE WHEN status NOT IN ('up', 'down') THEN 1 ELSE 0 END) as unknown_total
            ")
            ->whereBetween('created_at', [$effectiveStartDate, $effectiveEndDate])
            ->first();

        $incidentsCount = self::countOverlappingIncidents($monitoring, $effectiveStartDate, $effectiveEndDate);

        return self::buildUptimeDowntimeStats(
            $startDate,
            $endDate,
            $trackingStartedAt,
            $overallUptimeMinutes,
            $overallDowntimeMinutes,
            $overallUnknownMinutes,
            (int) ($data->uptime_total ?? 0),
            (int) ($data->downtime_total ?? 0),
            (int) ($data->unknown_total ?? 0),
            $incidentsCount
        );
    }

    private static function incrementMinutesByStatus(
        string|MonitoringStatus|null $status,
        int $minutes,
        int &$uptimeMinutes,
        int &$downtimeMinutes,
        int &$unknownMinutes
    ): void {
        if ($minutes <= 0) {
            return;
        }

        $statusValue = $status instanceof MonitoringStatus
            ? $status->value
            : $status;

        match ($statusValue) {
            MonitoringStatus::UP->value => $uptimeMinutes += $minutes,
            MonitoringStatus::DOWN->value => $downtimeMinutes += $minutes,
            default => $unknownMinutes += $minutes,
        };
    }

    private static function countOverlappingIncidents(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): int
    {
        return (int) $monitoring->incidents()
            ->where('down_at', '<=', $endDate)
            ->where(function (Builder $builder) use ($startDate) {
                $builder->where('up_at', '>=', $startDate)
                    ->orWhereNull('up_at');
            })
            ->count();
    }

    /**
     * Get the base query for monitoring responses, either from live or archived tables.
     */
    private static function getMonitoringResponseQuery(Carbon $endDate): Builder
    {
        // If the end date is older than 7 days, query the archived responses.
        if ($endDate->lt(Date::now()->subWeek()->startOfDay())) {
            return MonitoringResponseArchived::query();
        }

        // Otherwise, query the live monitoring responses.
        return MonitoringResponse::query();
    }

    /**
     * Get the grouping format for date-based queries based on the number of days in the period.
     *
     * @param  int  $days  The number of days in the period.
     * @return string The date format string for grouping (e.g., '%Y-%m-%d %H' for hourly, '%Y-%m-%d' for daily).
     */
    private static function getGrouping(int $days): string
    {
        return match (true) {
            $days <= 1 => '%Y-%m-%d %H',
            $days <= 30 => '%Y-%m-%d',
            default => '%Y-%m',
        };
    }

    private static function getPeriodExpression(string $column, string $format): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "strftime('{$format}', {$column})";
        }

        return "DATE_FORMAT({$column}, '{$format}')";
    }

    private static function buildUptimeDowntimeStats(
        Carbon $startDate,
        Carbon $endDate,
        ?Carbon $trackingStartedAt,
        int $uptimeMinutes,
        int $downtimeMinutes,
        int $unknownMinutes,
        int $uptimeTotal,
        int $downtimeTotal,
        int $unknownTotal,
        int $incidentsCount
    ): Collection {
        $totalTrackedMinutes = $uptimeMinutes + $downtimeMinutes + $unknownMinutes;
        $hasData = $totalTrackedMinutes > 0;

        return collect([
            'data' => [
                'from' => $startDate,
                'to' => $endDate,
            ],
            'has_data' => $hasData,
            'tracking_started_at' => $trackingStartedAt?->toIso8601String(),
            'uptime' => [
                'minutes' => $uptimeMinutes,
                'percentage' => $hasData ? ($uptimeMinutes / $totalTrackedMinutes) * 100 : null,
                'total' => $uptimeTotal,
            ],
            'downtime' => [
                'minutes' => $downtimeMinutes,
                'percentage' => $hasData ? ($downtimeMinutes / $totalTrackedMinutes) * 100 : null,
                'total' => $downtimeTotal,
                'incidents_count' => $incidentsCount,
            ],
            'unknown' => [
                'minutes' => $unknownMinutes,
                'percentage' => $hasData ? ($unknownMinutes / $totalTrackedMinutes) * 100 : null,
                'total' => $unknownTotal,
            ],
        ]);
    }

    private static function getTrackingStartedAt(Monitoring $monitoring): ?Carbon
    {
        $trackingStartedAt = collect([
            $monitoring->archivedResponseResults()->min('created_at'),
            $monitoring->responseResults()->min('created_at'),
        ])->filter()->map(fn ($date): Carbon => Date::parse($date))->sort()->first();

        return $trackingStartedAt instanceof Carbon ? $trackingStartedAt : null;
    }

    private static function getTrackingStartedAtFromDailyResults(Monitoring $monitoring): ?Carbon
    {
        $trackingStartedAt = $monitoring->dailyResults()->min('date');

        if (! $trackingStartedAt) {
            return null;
        }

        return Date::parse($trackingStartedAt)->startOfDay();
    }
}
