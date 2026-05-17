<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Monitoring;
use App\Support\MonitoringDateRange;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringDateRangeTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_past_days_resolves_stable_day_boundaries_and_cache_segment(): void
    {
        Date::setTestNow('2026-04-12 12:34:56');

        $range = MonitoringDateRange::pastDays(7);

        $this->assertSame(7, $range->days);
        $this->assertSame('2026-04-05 00:00:00', $range->startDate->toDateTimeString());
        $this->assertSame('2026-04-12 23:59:59', $range->endDate->toDateTimeString());
        $this->assertSame('20260405:20260412', $range->cacheDateSegment());
    }

    public function test_intraday_ranges_use_raw_uptime_data(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = new Monitoring();
        $monitoring->created_at = Date::now()->subDays(30);

        $range = MonitoringDateRange::pastDays(1);

        $this->assertTrue($range->isIntraday());
        $this->assertFalse($range->shouldUseUptimeAggregates($monitoring));
        $this->assertTrue($range->shouldIncludeIntradayRawData());
        $this->assertFalse($range->shouldUseResponseTimeAggregates());
    }

    public function test_multi_day_ranges_skip_uptime_aggregates_until_monitoring_has_a_full_day_of_history(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $newMonitoring = new Monitoring();
        $newMonitoring->created_at = Date::now()->subHours(12);

        $olderMonitoring = new Monitoring();
        $olderMonitoring->created_at = Date::now()->subDays(2);

        $range = MonitoringDateRange::pastDays(7);

        $this->assertFalse($range->isIntraday());
        $this->assertFalse($range->shouldUseUptimeAggregates($newMonitoring));
        $this->assertTrue($range->shouldUseUptimeAggregates($olderMonitoring));
        $this->assertFalse($range->shouldIncludeIntradayRawData());
        $this->assertTrue($range->shouldUseResponseTimeAggregates());
    }
}
