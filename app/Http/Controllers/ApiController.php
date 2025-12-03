<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Services\MonitoringResultService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

/**
 * Class ApiController
 *
 * This controller is responsible for handling all API requests related to monitoring data.
 * It provides endpoints for retrieving uptime/downtime, response times, incidents, and other monitoring statistics.
 * The controller makes extensive use of caching to ensure optimal performance.
 */
class ApiController extends Controller
{
    /**
     * The interval in seconds for how long the cronjob data should be cached.
     */
    protected int $cronjobInterval = 60;

    /**
     * Retrieves the uptime and downtime data for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @param  Request  $request  The HTTP request.
     * @return JsonResponse The JSON response containing the uptime and downtime data.
     */
    public function uptimeDowntime(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $loadAggregatedData = ($days > 1);

        $cacheKey = sprintf('monitoring:%s:uptime:%s:%s:%s', $monitoring->id, $days, $startDate->format('Ymd'), $endDate->format('Ymd'));

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): Collection => MonitoringResultService::getUptimeDowntime($monitoring, $startDate, $endDate, $loadAggregatedData),
            $this->cronjobInterval,
            'monitoring:'.$monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the response times for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @param  Request  $request  The HTTP request.
     * @return JsonResponse The JSON response containing the response times data.
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
            $this->cronjobInterval,
            'monitoring:'.$monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the uptime heatmap data for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @return JsonResponse The JSON response containing the uptime heatmap data.
     */
    public function uptimeHeatmap(Monitoring $monitoring): JsonResponse
    {
        $start_date = now()->subHours(23);
        $end_date = now();

        $cacheKey = sprintf('monitoring:%s:heatmap', $monitoring->id);

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): Collection => MonitoringResultService::getHeatmap($monitoring, $start_date, $end_date),
            now()->addMinutes(15),
            'monitoring:'.$monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the status since the last incident for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @return JsonResponse The JSON response containing the status since data.
     */
    public function statusSince(Monitoring $monitoring): JsonResponse
    {
        $data = MonitoringResultService::getStatusSince($monitoring);

        return response()->json($data);
    }

    /**
     * Retrieves the current status of a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @return JsonResponse The JSON response containing the current status data.
     */
    public function statusNow(Monitoring $monitoring): JsonResponse
    {
        $data = MonitoringResultService::getStatusNow($monitoring, $this->cronjobInterval);

        return response()->json($data);
    }

    /**
     * Retrieves the incidents for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @param  Request  $request  The HTTP request.
     * @return JsonResponse The JSON response containing the incidents data.
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
            $this->cronjobInterval,
            'monitoring:'.$monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the SSL status for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @return JsonResponse The JSON response containing the SSL status data.
     */
    public function sslStatus(Monitoring $monitoring): JsonResponse
    {
        $cacheKey = sprintf('monitoring:%s:ssl-status', $monitoring->id);

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): array => [
                'valid' => $monitoring->sslResult?->is_valid,
                'expiration' => optional($monitoring->sslResult?->expires_at)?->format('d.m.Y'),
                'issuer' => $monitoring->sslResult?->issuer,
                'issue_date' => optional($monitoring->sslResult?->issued_at)?->format('d.m.Y'),
            ],
            $this->cronjobInterval,
            'monitoring:'.$monitoring->id
        );

        return response()->json($data);
    }

    /**
     * Retrieves the uptime calendar data for a given monitoring instance.
     *
     * @param  Monitoring  $monitoring  The monitoring instance.
     * @param  Request  $request  The HTTP request.
     * @return JsonResponse The JSON response containing the uptime calendar data.
     */
    public function uptimeCalendar(Monitoring $monitoring, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = Date::parse($validated['start_date'])->startOfDay();
        $endDate = Date::parse($validated['end_date'])->endOfDay();

        $cacheKey = 'monitoring_daily_uptime_calendar_'.$monitoring->id.'_'.$startDate->toDateString().'_'.$endDate->toDateString();

        $data = $this->cacheAndReturn(
            $cacheKey,
            fn (): array => MonitoringResultService::getUpTimeGroupByDateAndMonth($monitoring, $startDate, $endDate),
            3600, // Cache for 1 hour
            'monitoring:'.$monitoring->id
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
}
