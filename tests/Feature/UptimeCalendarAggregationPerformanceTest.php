<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UptimeCalendarAggregationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_generation_avoids_raw_requeries_for_monthly_averages(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::parse('2025-03-01 00:00:00'),
        ]);

        $cursor = Date::parse('2025-03-01')->startOfMonth();
        $end = Date::parse('2026-02-01')->startOfMonth();

        while ($cursor->lte($end)) {
            MonitoringDailyResult::query()->create([
                'monitoring_id' => $monitoring->id,
                'date' => $cursor->toDateString(),
                'uptime_total' => 100,
                'downtime_total' => 0,
                'uptime_percentage' => 100.0,
                'downtime_percentage' => 0.0,
                'uptime_minutes' => 24 * 60,
                'downtime_minutes' => 0,
                'avg_response_time' => 120.0,
                'min_response_time' => 100,
                'max_response_time' => 150,
                'incidents_count' => 0,
            ]);

            $cursor->addMonth();
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = MonitoringResultService::getUpTimeGroupByDateAndMonth(
            $monitoring,
            Date::parse('2025-03-01')->startOfDay(),
            Date::parse('2026-02-28')->endOfDay()
        );

        $selectCount = collect(DB::getQueryLog())
            ->filter(fn (array $entry): bool => str_starts_with(mb_strtolower($entry['query']), 'select'))
            ->count();

        $this->assertArrayHasKey('2025-03', $result);
        $this->assertArrayHasKey('2026-02', $result);
        $this->assertLessThanOrEqual(1, $selectCount);
    }
}
