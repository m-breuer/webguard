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
        $this->authorizeMonitoringDataAccess($monitoring);

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
        $this->authorizeMonitoringDataAccess($monitoring);

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
     * Retrieves uptime and downtime data for multiple day ranges in one request.
     *
     * @queryParam days[] integer[] The day ranges to retrieve. Example: [7, 30, 90]
     *
     * @response {
     *   "data": {
     *     "7": {
     *       "has_data": true
     *     },
     *     "30": {
     *       "has_data": true
     *     }
     *   }
     * }
     */
    public function uptimeDowntimeSummary(Monitoring $monitoring, Request $request): JsonResponse
    {
        $this->authorizeMonitoringDataAccess($monitoring);

        $validated = $request->validate([
            'days' => ['required', 'array', 'min:1', 'max:10'],
            'days.*' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        /** @var Collection<int, int> $days */
        $days = collect($validated['days'])
            ->map(static fn (mixed $day): int => (int) $day)
            ->unique()
            ->sort()
            ->values();

        $endDate = now()->endOfDay();
        $cacheKey = sprintf(
            'monitoring:%s:uptime-summary:%s:%s',
            $monitoring->id,
            $days->implode('-'),
            $endDate->format('Ymd')
        );

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): array => MonitoringResultService::getUptimeDowntimesForRanges($monitoring, $days->all()),
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json([
            'data' => $data,
        ]);
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
        $this->authorizeMonitoringDataAccess($monitoring);

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
     * @queryParam offset integer Optional number of entries to skip for pagination. Defaults to 0.
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
     *     "offset": 0,
     *     "days": 7,
     *     "has_more": false,
     *     "next_offset": null
     *   }
     * }
     */
    public function checks(Monitoring $monitoring, Request $request): JsonResponse
    {
        $this->authorizeMonitoringDataAccess($monitoring);

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $days = isset($validated['days']) ? (int) $validated['days'] : null;
        $limit = (int) ($validated['limit'] ?? 100);
        $offset = (int) ($validated['offset'] ?? 0);
        $pageSize = $limit + 1;
        $startDate = $days !== null ? now()->subDays($days)->startOfDay() : null;
        $endDate = now()->endOfDay();

        $cacheKey = sprintf(
            'monitoring:%s:checks:%s:%s:%s',
            $monitoring->id,
            $days ?? 'all',
            $limit,
            $offset
        );

        $data = $this->cacheAndReturn(
            $cacheKey,
            function () use ($monitoring, $startDate, $endDate, $offset, $pageSize, $limit): array {
                $archiveCutoffDate = Date::now()->subWeek()->startOfDay();

                if ($startDate !== null && $startDate->gte($archiveCutoffDate)) {
                    $rows = $this->buildChecksSourceQuery(
                        'monitoring_response_results',
                        'live',
                        $monitoring->id,
                        $startDate,
                        $endDate,
                        $offset,
                        $pageSize
                    )->get();

                    return $this->paginateCheckRows($rows, $limit, $offset);
                }

                if ($startDate === null) {
                    $liveRows = $this->buildChecksSourceQuery(
                        'monitoring_response_results',
                        'live',
                        $monitoring->id,
                        null,
                        null,
                        $offset,
                        $pageSize
                    )->get();

                    $oldestLiveCheckedAt = $liveRows->last()?->created_at;

                    if (
                        $liveRows->count() === $pageSize
                        && $oldestLiveCheckedAt !== null
                        && Date::parse((string) $oldestLiveCheckedAt)->gte($archiveCutoffDate)
                    ) {
                        return $this->paginateCheckRows($liveRows, $limit, $offset);
                    }
                }

                $rows = $this->buildChecksUnionQuery(
                    $monitoring->id,
                    $startDate,
                    $startDate !== null ? $endDate : null
                )
                    ->offset($offset)
                    ->limit($pageSize)
                    ->get();

                return $this->paginateCheckRows($rows, $limit, $offset);
            },
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

        return response()->json([
            'data' => $data['data'],
            'meta' => [
                'count' => count($data['data']),
                'limit' => $limit,
                'offset' => $offset,
                'days' => $days,
                'has_more' => $data['has_more'],
                'next_offset' => $data['next_offset'],
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
        $this->authorizeMonitoringDataAccess($monitoring);

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
        $this->authorizeMonitoringDataAccess($monitoring);

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
     * Retrieves the public embeddable widget payload for a monitoring instance.
     *
     * @response {
     *   "name": "Primary API",
     *   "status": "up",
     *   "status_label": "UP",
     *   "status_code": 200,
     *   "status_identifier": "status.success",
     *   "status_key": "notifications.status.success",
     *   "checked_at": "2026-04-12T10:00:00Z",
     *   "checked_at_human": "5 minutes ago",
     *   "uptime": {
     *     "7_days": 100,
     *     "30_days": 99.9,
     *     "365_days": 99.1
     *   },
     *   "public_url": "https://example.com/label/01H..."
     * }
     */
    public function widget(Monitoring $monitoring): JsonResponse
    {
        abort_unless($monitoring->public_label_enabled, 404);

        $cacheKey = sprintf('monitoring:%s:widget', $monitoring->id);

        $data = $this->cacheAndReturn(
            $cacheKey,
            function () use ($monitoring): array {
                $statusSince = MonitoringResultService::getStatusSince($monitoring);
                $statusNow = MonitoringResultService::getStatusNow($monitoring);
                $latestStatusCode = $monitoring->latestResponseResult?->http_status_code;
                $status = (string) ($statusSince['status'] ?? 'unknown');
                $checkedAt = $statusNow['checked_at'] ?? null;

                return [
                    'name' => $monitoring->name,
                    'status' => $status,
                    'status_label' => mb_strtoupper($status),
                    'status_code' => $latestStatusCode,
                    'status_identifier' => MonitoringStatusMeta::statusIdentifier($latestStatusCode, $monitoring->isUnderMaintenance()),
                    'status_key' => MonitoringStatusMeta::statusKey($latestStatusCode, $monitoring->isUnderMaintenance()),
                    'checked_at' => $checkedAt,
                    'checked_at_human' => $checkedAt ? Date::parse((string) $checkedAt)->diffForHumans() : null,
                    'uptime' => [
                        '7_days' => $this->resolveWidgetUptimePercentage($monitoring, 7),
                        '30_days' => $this->resolveWidgetUptimePercentage($monitoring, 30),
                        '365_days' => $this->resolveWidgetUptimePercentage($monitoring, 365),
                    ],
                    'public_url' => route('public-label', $monitoring),
                ];
            },
            (int) config('monitoring.interval', 5) * 60,
            'monitoring:' . $monitoring->id
        );

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
        $this->authorizeMonitoringDataAccess($monitoring);

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
        $this->authorizeMonitoringDataAccess($monitoring);

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
        $this->authorizeMonitoringDataAccess($monitoring);

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

    private function authorizeMonitoringDataAccess(Monitoring $monitoring): void
    {
        $user = request()->user();

        if ($user && $monitoring->user_id === $user->id) {
            return;
        }

        abort_unless($monitoring->public_label_enabled, 404);
    }

    private function resolveWidgetUptimePercentage(Monitoring $monitoring, int $days): ?float
    {
        $startDate = Date::now()->subDays($days)->startOfDay();
        $endDate = Date::now()->endOfDay();
        $loadAggregatedData = $days > 1 && $monitoring->created_at->diffInDays(Date::now()) >= 1;

        $stats = MonitoringResultService::getUptimeDowntime(
            $monitoring,
            $startDate,
            $endDate,
            $loadAggregatedData,
            false
        );

        return data_get($stats, 'uptime.percentage');
    }

    private function buildChecksSourceQuery(
        string $table,
        string $source,
        string $monitoringId,
        ?Carbon $startDate,
        ?Carbon $endDate,
        int $offset,
        int $limit
    ): QueryBuilder {
        return $this->buildChecksSourceSubquery($table, $source, $monitoringId, $startDate, $endDate)->latest()
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit);
    }

    private function buildChecksUnionQuery(string $monitoringId, ?Carbon $startDate, ?Carbon $endDate): QueryBuilder
    {
        $builder = $this->buildChecksSourceSubquery(
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
            ->fromSub($builder->unionAll($archivedQuery), 'monitoring_results')->latest()
            ->orderByDesc('id');
    }

    private function buildChecksSourceSubquery(
        string $table,
        string $source,
        string $monitoringId,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): QueryBuilder {
        $builder = DB::table($table)
            ->selectRaw("'{$source}' as source, id, status, http_status_code, response_time, created_at")
            ->where('monitoring_id', $monitoringId);

        if ($startDate !== null && $endDate !== null) {
            $builder->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $builder;
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

    /**
     * @param  Collection<int, object>  $rows
     * @return array{data: array<int, array{id: string, checked_at: string, status: string, http_status_code: int|null, response_time: float|null, status_identifier: string, status_key: string, source: string}>, has_more: bool, next_offset: int|null}
     */
    private function paginateCheckRows(Collection $rows, int $limit, int $offset): array
    {
        $hasMore = $rows->count() > $limit;
        $pageRows = $rows->take($limit);

        return [
            'data' => $this->formatCheckRows($pageRows),
            'has_more' => $hasMore,
            'next_offset' => $hasMore ? $offset + $pageRows->count() : null,
        ];
    }
}
