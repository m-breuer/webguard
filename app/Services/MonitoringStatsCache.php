<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitoring;
use App\Support\MonitoringDateRange;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

class MonitoringStatsCache
{
    public function __construct(private readonly MonitoringCheckIntervalService $monitoringCheckIntervalService) {}

    /**
     * @return array<int, string>
     */
    public function tags(Monitoring $monitoring): array
    {
        return ['monitoring:' . $monitoring->id];
    }

    public function defaultTtlSeconds(Monitoring $monitoring): int
    {
        return $this->monitoringCheckIntervalService->secondsFor($monitoring);
    }

    public function calendarTtlSeconds(): int
    {
        return 3600;
    }

    public function heatmapExpiresAt(): DateTimeInterface
    {
        return Date::now()->addMinutes(5);
    }

    public function shouldCache(): bool
    {
        return app()->isProduction();
    }

    public function remember(
        Monitoring $monitoring,
        string $cacheKey,
        callable $callback,
        int|DateTimeInterface|null $ttl = null
    ): mixed {
        if ($this->shouldCache()) {
            return Cache::tags($this->tags($monitoring))
                ->remember($cacheKey, $ttl ?? $this->defaultTtlSeconds($monitoring), $callback);
        }

        return $callback();
    }

    public function get(Monitoring $monitoring, string $cacheKey): mixed
    {
        if (! $this->shouldCache()) {
            return null;
        }

        return Cache::tags($this->tags($monitoring))->get($cacheKey);
    }

    public function put(
        Monitoring $monitoring,
        string $cacheKey,
        mixed $value,
        int|DateTimeInterface|null $ttl = null
    ): void {
        if ($this->shouldCache()) {
            Cache::tags($this->tags($monitoring))
                ->put($cacheKey, $value, $ttl ?? $this->defaultTtlSeconds($monitoring));
        }
    }

    public function dashboardKey(
        Monitoring $monitoring,
        int $days,
        MonitoringDateRange $monitoringDateRange,
        Carbon $calendarStartDate,
        Carbon $calendarEndDate
    ): string {
        return sprintf(
            'monitoring:%s:all:%s:%s:%s:%s',
            $monitoring->id,
            $days,
            $monitoringDateRange->cacheDateSegment(),
            $calendarStartDate->toDateString(),
            $calendarEndDate->toDateString()
        );
    }

    public function uptimeKey(Monitoring $monitoring, int $days, MonitoringDateRange $monitoringDateRange): string
    {
        return sprintf('monitoring:%s:uptime:%s:%s', $monitoring->id, $days, $monitoringDateRange->cacheDateSegment());
    }

    /**
     * @param  Collection<int, int>  $days
     */
    public function uptimeSummaryKey(Monitoring $monitoring, Collection $days, Carbon $endDate): string
    {
        return sprintf(
            'monitoring:%s:uptime-summary:%s:%s',
            $monitoring->id,
            $days->implode('-'),
            $endDate->format('Ymd')
        );
    }

    public function responseTimesKey(Monitoring $monitoring, int $days, MonitoringDateRange $monitoringDateRange): string
    {
        return sprintf('monitoring:%s:response:%s:%s', $monitoring->id, $days, $monitoringDateRange->cacheDateSegment());
    }

    public function serverHealthTelemetryKey(Monitoring $monitoring, int $days, MonitoringDateRange $monitoringDateRange): string
    {
        return sprintf('monitoring:%s:server-health-telemetry:%s:%s', $monitoring->id, $days, $monitoringDateRange->cacheDateSegment());
    }

    public function checksKey(Monitoring $monitoring, ?int $days, int $limit, int $offset): string
    {
        return sprintf(
            'monitoring:%s:checks:%s:%s:%s',
            $monitoring->id,
            $days ?? 'all',
            $limit,
            $offset
        );
    }

    public function heatmapKey(Monitoring $monitoring): string
    {
        return sprintf('monitoring:%s:heatmap', $monitoring->id);
    }

    public function badgeKey(Monitoring $monitoring): string
    {
        return sprintf('monitoring:%s:badge', $monitoring->id);
    }

    public function incidentsKey(Monitoring $monitoring, int $days, MonitoringDateRange $monitoringDateRange): string
    {
        return sprintf('monitoring:%s:incidents:%s:%s', $monitoring->id, $days, $monitoringDateRange->cacheDateSegment());
    }

    public function sslStatusKey(Monitoring $monitoring): string
    {
        return sprintf('monitoring:%s:ssl-status', $monitoring->id);
    }

    public function uptimeCalendarKey(Monitoring $monitoring, Carbon $startDate, Carbon $endDate): string
    {
        return 'monitoring_daily_uptime_calendar_' . $monitoring->id . '_' . $startDate->toDateString() . '_' . $endDate->toDateString();
    }
}
