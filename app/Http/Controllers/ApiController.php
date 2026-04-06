<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Services\MonitoringResultService;
use App\Support\MonitoringStatusMeta;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * @group Public API
 *
 * This controller is responsible for handling all API requests related to monitoring data.
 * It provides endpoints for retrieving uptime/downtime, response times, incidents, and other monitoring statistics.
 * The controller makes extensive use of caching to ensure optimal performance.
 */
class ApiController extends Controller
{
    /**
     * Retrieves all data for a given monitoring instance.
     *
     * @response {
     *  "status_since": {
     *  "status": "UP",
     *  "time": "2021-01-01 00:00:00"
     *  },
     *  "status_now": {
     *  "status": "UP"
     *  },
     *  "uptime_downtime": [
     *  {
     *  "date": "2021-01-01",
     *  "uptime": 100,
     *  "downtime": 0
     *  }
     *  ],
     *  "response_times": [
     *  {
     *  "datetime": "2021-01-01 00:00:00",
     *  "response_time": 123
     *  }
     *  ],
     *  "incidents": [
     *  {
     *  "started_at": "2021-01-01 00:00:00",
     *  "finished_at": "2021-01-01 00:05:00",
     *  "type": "DOWN",
     *  "reason": "HTTP status code 500"
     *  }
     *  ],
     *  "heatmap": [
     *  {
     *  "hour": "00:00",
     *  "uptime": 100
     *  }
     *  ],
     *  "ssl": {
     *  "valid": true,
     *  "expiration": "2022-01-01T00:00:00.000000Z",
     *  "issuer": "Let's Encrypt",
     *  "issue_date": "2021-10-01T00:00:00.000000Z"
     *  },
     *  "uptime_calendar": {
     *  "2021-01": [
     *  {
     *  "date": "2021-01-01",
     *  "uptime": "100.00"
     *  }
     *  ]
     *  }
     * }
     */
    public function all(Monitoring $monitoring, Request $request): JsonResponse
    {
        $data = [
            'status_since' => $this->statusSince($monitoring)->getData(),
            'status_now' => $this->statusNow($monitoring)->getData(),
            'uptime_downtime' => $this->uptimeDowntime($monitoring, $request)->getData(),
            'response_times' => $this->responseTimes($monitoring, $request)->getData(),
            'incidents' => $this->incidents($monitoring, $request)->getData(),
            'heatmap' => $this->uptimeHeatmap($monitoring)->getData(),
            'ssl' => $this->sslStatus($monitoring)->getData(),
            'uptime_calendar' => $this->uptimeCalendar($monitoring, $request)->getData(),
        ];

        return response()->json($data);
    }

    /**
     * Retrieves the uptime and downtime data for a given monitoring instance.
     *
     * @queryParam days integer The number of days to retrieve data for. Defaults to 30. Example: 30
     *
     * @response [
     * {
     * "date": "2021-01-01",
     * "uptime": 100,
     * "downtime": 0
     * }
     * ]
     */
    public function uptimeDowntime(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();
        $isIntradayRange = $days <= 1;

        $loadAggregatedData = ! $isIntradayRange;

        if ($monitoring->created_at->diffInDays(now()) < 1) {
            $loadAggregatedData = false;
        }

        $includeIntradayRawData = $isIntradayRange;

        $cacheKey = sprintf('monitoring:%s:uptime:%s:%s:%s', $monitoring->id, $days, $startDate->format('Ymd'), $endDate->format('Ymd'));

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): Collection => MonitoringResultService::getUptimeDowntime($monitoring, $startDate, $endDate, $loadAggregatedData, $includeIntradayRawData),
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the response times for a given monitoring instance.
     *
     * @queryParam days integer The number of days to retrieve data for. Defaults to 30. Example: 30
     *
     * @response [
     * {
     * "datetime": "2021-01-01 00:00:00",
     * "response_time": 123
     * }
     * ]
     */
    public function responseTimes(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $loadAggregatedData = ($days > 1);

        $cacheKey = sprintf('monitoring:%s:response:%s:%s:%s', $monitoring->id, $days, $startDate->format('Ymd'), $endDate->format('Ymd'));

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): Collection => MonitoringResultService::getResponseTimes($monitoring, $startDate, $endDate, $loadAggregatedData),
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves historical monitoring checks including status code details.
     *
     * @queryParam days integer Optional number of past days to include. If omitted, all available history is considered.
     * @queryParam limit integer Optional maximum number of entries returned. Defaults to 100.
     *
     * @response {
     *   "data": [
     *     {
     *       "id": "01H...",
     *       "checked_at": "2026-03-24T12:00:00Z",
     *       "status": "down",
     *       "http_status_code": 503,
     *       "response_time": 210.5,
     *       "status_identifier": "status.server_error",
     *       "status_key": "notifications.status.server_error",
     *       "source": "live"
     *     }
     *   ],
     *   "meta": {
     *     "count": 1,
     *     "limit": 100,
     *     "days": 7
     *   }
     * }
     */
    public function checks(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $days = isset($validated['days']) ? (int) $validated['days'] : null;
        $limit = (int) ($validated['limit'] ?? 100);
        $startDate = $days !== null ? now()->subDays($days)->startOfDay() : null;
        $endDate = now()->endOfDay();

        $cacheKey = sprintf(
            'monitoring:%s:checks:%s:%s',
            $monitoring->id,
            $days ?? 'all',
            $limit
        );

        $data = $this->cacheAndReturn(
            $cacheKey,
            function () use ($monitoring, $startDate, $endDate, $limit): array {
                $archiveCutoffDate = Date::now()->subWeek()->startOfDay();

                if ($startDate !== null && $startDate->gte($archiveCutoffDate)) {
                    $rows = $this->buildChecksSourceQuery(
                        'monitoring_response_results',
                        'live',
                        $monitoring->id,
                        $startDate,
                        $endDate,
                        $limit
                    )->get();

                    return $this->formatCheckRows($rows);
                }

                if ($startDate === null) {
                    $liveRows = $this->buildChecksSourceQuery(
                        'monitoring_response_results',
                        'live',
                        $monitoring->id,
                        null,
                        null,
                        $limit
                    )->get();

                    $oldestLiveCheckedAt = $liveRows->last()?->created_at;

                    if (
                        $liveRows->count() === $limit
                        && $oldestLiveCheckedAt !== null
                        && Date::parse((string) $oldestLiveCheckedAt)->gte($archiveCutoffDate)
                    ) {
                        return $this->formatCheckRows($liveRows);
                    }
                }

                $rows = $this->buildChecksUnionQuery(
                    $monitoring->id,
                    $startDate,
                    $startDate !== null ? $endDate : null
                )
                    ->limit($limit)
                    ->get();

                return $this->formatCheckRows($rows);
            },
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json([
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'limit' => $limit,
                'days' => $days,
            ],
        ]);
    }

    /**
     * Retrieves the uptime heatmap data for a given monitoring instance.
     *
     * @response [
     * {
     * "hour": "00:00",
     * "uptime": 100
     * }
     * ]
     */
    public function uptimeHeatmap(Monitoring $monitoring): JsonResponse
    {
        $start_date = now()->subHours(23);
        $end_date = now();

        $cacheKey = sprintf('monitoring:%s:heatmap', $monitoring->id);

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): Collection => MonitoringResultService::getHeatmap($monitoring, $start_date, $end_date),
            now()->addMinutes(5),
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the combined status of a given monitoring instance.
     *
     * @response {
     * "status": "UP",
     * "since": "2021-01-01 00:00:00",
     * "checked_at": "2021-01-01 00:00:00",
     * "next": "2021-01-01 00:05:00",
     * "interval": 300
     * }
     */
    public function status(Monitoring $monitoring): JsonResponse
    {
        $statusSince = MonitoringResultService::getStatusSince($monitoring);
        $statusNow = MonitoringResultService::getStatusNow($monitoring);
        $latestStatusCode = $monitoring->latestResponseResult?->http_status_code;

        $data = array_merge($statusSince, $statusNow, [
            'status_code' => $latestStatusCode,
            'status_changed_at' => $statusSince['since'] ?? null,
            'status_identifier' => MonitoringStatusMeta::statusIdentifier($latestStatusCode, $monitoring->isUnderMaintenance()),
            'status_key' => MonitoringStatusMeta::statusKey($latestStatusCode, $monitoring->isUnderMaintenance()),
            'monitoring' => [
                'name' => $monitoring->name,
                'target' => $monitoring->target,
                'type' => $monitoring->type->value,
            ],
        ]);

        return response()->json($data);
    }

    /**
     * Retrieves the incidents for a given monitoring instance.
     *
     * @queryParam days integer The number of days to retrieve data for. Defaults to 30. Example: 30
     *
     * @response [
     * {
     * "started_at": "2021-01-01 00:00:00",
     * "finished_at": "2021-01-01 00:05:00",
     * "type": "DOWN",
     * "reason": "HTTP status code 500"
     * }
     * ]
     */
    public function incidents(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $cacheKey = sprintf('monitoring:%s:incidents:%s:%s:%s', $monitoring->id, $days, $startDate->format('Ymd'), $endDate->format('Ymd'));

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): Collection => MonitoringResultService::getIncidents($monitoring, $startDate, $endDate),
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves uptime percentage and incident count for a custom date range.
     *
     * @queryParam from date required Range start date. Example: 2026-01-01
     * @queryParam until date required Range end date. Example: 2026-01-31
     *
     * @response {
     *   "from": "2026-01-01",
     *   "until": "2026-01-31",
     *   "uptime_percentage": 99.85,
     *   "has_data": true,
     *   "tracking_started_at": "2026-01-15T12:00:00Z",
     *   "incidents_count": 2
     * }
     */
    public function customRangeStats(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $startDate = Date::parse($validated['from'])->startOfDay();
        $endDate = Date::parse($validated['until'])->endOfDay();
        $isIntradayRange = $startDate->isSameDay($endDate);

        $loadAggregatedData = ! $isIntradayRange;

        if ($monitoring->created_at->diffInDays(now()) < 1) {
            $loadAggregatedData = false;
        }

        $includeIntradayRawData = $isIntradayRange;

        $cacheKey = sprintf(
            'monitoring:%s:custom-range-stats:%s:%s',
            $monitoring->id,
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        $data = $this->cacheAndReturn(
            $cacheKey,
            function () use ($monitoring, $startDate, $endDate, $loadAggregatedData, $includeIntradayRawData): array {
                $uptimeDowntime = MonitoringResultService::getUptimeDowntime(
                    $monitoring,
                    $startDate,
                    $endDate,
                    $loadAggregatedData,
                    $includeIntradayRawData
                );
                $incidentsCount = $loadAggregatedData
                    ? MonitoringResultService::getAggregatedIncidentsCount($monitoring, $startDate, $endDate, $includeIntradayRawData)
                    : MonitoringResultService::countIncidents($monitoring, $startDate, $endDate);

                return [
                    'from' => $startDate->toDateString(),
                    'until' => $endDate->toDateString(),
                    'uptime_percentage' => $uptimeDowntime['uptime']['percentage'],
                    'has_data' => (bool) ($uptimeDowntime['has_data'] ?? false),
                    'tracking_started_at' => $uptimeDowntime['tracking_started_at'],
                    'incidents_count' => $incidentsCount,
                ];
            },
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the SSL status for a given monitoring instance.
     *
     * @response {
     * "valid": true,
     * "expiration": "2022-01-01T00:00:00.000000Z",
     * "issuer": "Let's Encrypt",
     * "issue_date": "2021-10-01T00:00:00.000000Z"
     * }
     */
    public function sslStatus(Monitoring $monitoring): JsonResponse
    {
        $cacheKey = sprintf('monitoring:%s:ssl-status', $monitoring->id);

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): array => [
                'valid' => $monitoring->sslResult?->is_valid,
                'expiration' => optional($monitoring->sslResult?->expires_at)?->toIso8601String(),
                'issuer' => $monitoring->sslResult?->issuer,
                'issue_date' => optional($monitoring->sslResult?->issued_at)?->toIso8601String(),
            ],
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the uptime calendar data for a given monitoring instance.
     *
     * @queryParam start_date date required The start date to retrieve data for. Example: 2021-01-01
     * @queryParam end_date date required The end date to retrieve data for. Example: 2021-01-31
     *
     * @response {
     * "2021-01": [
     * {
     * "date": "2021-01-01",
     * "uptime": "100.00"
     * }
     * ]
     * }
     */
    public function uptimeCalendar(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = Date::parse($validated['start_date'])->startOfDay();
        $endDate = Date::parse($validated['end_date'])->endOfDay();

        $cacheKey = 'monitoring_daily_uptime_calendar_' . $monitoring->id . '_' . $startDate->toDateString() . '_' . $endDate->toDateString();

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): array => MonitoringResultService::getUpTimeGroupByDateAndMonth($monitoring, $startDate, $endDate),
            3600, // Cache for 1 hour
            'monitoring:' . $monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Caches the result of a callback function and returns it.
     *
     * This method provides a convenient way to cache data with tags.
     * Caching is only enabled in the production environment.
     *
     * @param  string  $cacheKey  The cache key to use for storing the data.
     * @param  callable  $callback  The callback function that generates the data to be cached.
     * @param  int|DateTimeInterface  $ttl  The time-to-live for the cache entry.
     * @param  string|array  $tags  The cache tags to apply to the entry.
     * @return mixed The result of the callback function, either from the cache or freshly generated.
     */
    protected function cacheAndReturn(string $cacheKey, callable $callback, int|DateTimeInterface $ttl, string|array $tags): mixed
    {
        if (env('APP_ENV') === 'production') {
            return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
        }

        return $callback();
    }

    private function buildChecksSourceQuery(
        string $table,
        string $source,
        string $monitoringId,
        ?Carbon $startDate,
        ?Carbon $endDate,
        int $limit
    ): QueryBuilder {
        return $this->buildChecksSourceSubquery($table, $source, $monitoringId, $startDate, $endDate)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit);
    }

    private function buildChecksUnionQuery(string $monitoringId, ?Carbon $startDate, ?Carbon $endDate): QueryBuilder
    {
        $liveQuery = $this->buildChecksSourceSubquery(
            'monitoring_response_results',
            'live',
            $monitoringId,
            $startDate,
            $endDate
        );
        $archivedQuery = $this->buildChecksSourceSubquery(
            'monitoring_response_archived',
            'archived',
            $monitoringId,
            $startDate,
            $endDate
        );

        return DB::query()
            ->fromSub($liveQuery->unionAll($archivedQuery), 'monitoring_results')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function buildChecksSourceSubquery(
        string $table,
        string $source,
        string $monitoringId,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): QueryBuilder {
        $query = DB::table($table)
            ->selectRaw("'{$source}' as source, id, status, http_status_code, response_time, created_at")
            ->where('monitoring_id', $monitoringId);

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array{id: string, checked_at: string, status: string, http_status_code: int|null, response_time: float|null, status_identifier: string, status_key: string, source: string}>
     */
    private function formatCheckRows(Collection $rows): array
    {
        return $rows->map(function (object $row): array {
            $httpStatusCode = $row->http_status_code !== null ? (int) $row->http_status_code : null;

            return [
                'id' => (string) $row->id,
                'checked_at' => Date::parse((string) $row->created_at)->toIso8601String(),
                'status' => (string) $row->status,
                'http_status_code' => $httpStatusCode,
                'response_time' => $row->response_time !== null ? (float) $row->response_time : null,
                'status_identifier' => MonitoringStatusMeta::statusIdentifier($httpStatusCode),
                'status_key' => MonitoringStatusMeta::statusKey($httpStatusCode),
                'source' => (string) $row->source,
            ];
        })->all();
    }
}
