<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Monitoring;
use App\Services\MonitoringStatsCache;
use App\Support\MonitoringDateRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringStatsCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_cache_tags_and_ttls_are_defined_in_one_place(): void
    {
        config(['monitoring.interval' => 7]);

        $cache = app(MonitoringStatsCache::class);
        $monitoring = $this->monitoring('monitoring-123');

        $this->assertSame(['monitoring:monitoring-123'], $cache->tags($monitoring));
        $this->assertSame(420, $cache->defaultTtlSeconds());
        $this->assertSame(3600, $cache->calendarTtlSeconds());
    }

    public function test_heatmap_expiration_uses_five_minutes_from_now(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $expiresAt = app(MonitoringStatsCache::class)->heatmapExpiresAt();

        $this->assertSame('2026-04-12 12:05:00', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_remember_bypasses_cache_outside_production(): void
    {
        $cache = app(MonitoringStatsCache::class);
        $monitoring = $this->monitoring('monitoring-123');
        $calls = 0;

        $firstResult = $cache->remember($monitoring, 'monitoring:monitoring-123:test', function () use (&$calls): string {
            $calls++;

            return 'value-' . $calls;
        });

        $secondResult = $cache->remember($monitoring, 'monitoring:monitoring-123:test', function () use (&$calls): string {
            $calls++;

            return 'value-' . $calls;
        });

        $this->assertFalse($cache->shouldCache());
        $this->assertSame('value-1', $firstResult);
        $this->assertSame('value-2', $secondResult);
        $this->assertSame(2, $calls);
    }

    public function test_monitoring_stats_cache_builds_endpoint_specific_keys(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $cache = app(MonitoringStatsCache::class);
        $monitoring = $this->monitoring('monitoring-123');
        $range = MonitoringDateRange::pastDays(7);
        $calendarStartDate = Date::now()->startOfMonth();
        $calendarEndDate = Date::now()->endOfDay();

        $this->assertSame(
            'monitoring:monitoring-123:all:7:20260405:20260412:2026-04-01:2026-04-12',
            $cache->dashboardKey($monitoring, 7, $range, $calendarStartDate, $calendarEndDate)
        );
        $this->assertSame('monitoring:monitoring-123:uptime:7:20260405:20260412', $cache->uptimeKey($monitoring, 7, $range));
        $this->assertSame(
            'monitoring:monitoring-123:uptime-summary:7-30-90:20260412',
            $cache->uptimeSummaryKey($monitoring, Collection::make([7, 30, 90]), $calendarEndDate)
        );
        $this->assertSame('monitoring:monitoring-123:response:7:20260405:20260412', $cache->responseTimesKey($monitoring, 7, $range));
        $this->assertSame('monitoring:monitoring-123:checks:all:100:0', $cache->checksKey($monitoring, null, 100, 0));
        $this->assertSame('monitoring:monitoring-123:checks:7:50:10', $cache->checksKey($monitoring, 7, 50, 10));
        $this->assertSame('monitoring:monitoring-123:heatmap', $cache->heatmapKey($monitoring));
        $this->assertSame('monitoring:monitoring-123:widget', $cache->widgetKey($monitoring));
        $this->assertSame('monitoring:monitoring-123:incidents:7:20260405:20260412', $cache->incidentsKey($monitoring, 7, $range));
        $this->assertSame('monitoring:monitoring-123:ssl-status', $cache->sslStatusKey($monitoring));
        $this->assertSame(
            'monitoring_daily_uptime_calendar_monitoring-123_2026-04-01_2026-04-12',
            $cache->uptimeCalendarKey($monitoring, $calendarStartDate, $calendarEndDate)
        );
    }

    private function monitoring(string $id): Monitoring
    {
        $monitoring = new Monitoring();
        $monitoring->id = $id;

        return $monitoring;
    }
}
