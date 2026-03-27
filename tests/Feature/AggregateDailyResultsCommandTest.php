<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class AggregateDailyResultsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_aggregation_does_not_backfill_pre_tracking_time_as_uptime(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subDays(2),
        ]);

        $trackingStartedAt = Date::parse('2026-03-17 12:00:00');

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'response_time' => null,
            'created_at' => $trackingStartedAt,
            'updated_at' => $trackingStartedAt,
        ]);

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => $trackingStartedAt,
            'up_at' => null,
        ]);

        Artisan::call('monitoring:aggregate-daily', ['days' => 1]);

        $dailyResult = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereDate('date', '2026-03-17')
            ->first();

        $this->assertNotNull($dailyResult);
        $this->assertSame(0, $dailyResult->uptime_minutes);
        $this->assertGreaterThan(0, $dailyResult->downtime_minutes);
        $this->assertSame(0.0, $dailyResult->uptime_percentage);
        $this->assertSame(100.0, $dailyResult->downtime_percentage);
    }

    public function test_daily_aggregation_tracks_unknown_separately_without_inflating_uptime_or_downtime(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::parse('2026-03-15 00:00:00'),
        ]);

        $unknownAt = Date::parse('2026-03-17 00:00:00');
        $upAt = Date::parse('2026-03-17 08:00:00');
        $downAt = Date::parse('2026-03-17 16:00:00');

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UNKNOWN,
            'response_time' => null,
            'created_at' => $unknownAt,
            'updated_at' => $unknownAt,
        ]);

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 120,
            'created_at' => $upAt,
            'updated_at' => $upAt,
        ]);

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'response_time' => null,
            'created_at' => $downAt,
            'updated_at' => $downAt,
        ]);

        Artisan::call('monitoring:aggregate-daily', ['days' => 1]);

        $dailyResult = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereDate('date', '2026-03-17')
            ->first();

        $this->assertNotNull($dailyResult);
        $this->assertSame(480, $dailyResult->uptime_minutes);
        $this->assertSame(479, $dailyResult->downtime_minutes);
        $this->assertSame(480, $dailyResult->unknown_minutes);
        $this->assertSame(1, $dailyResult->uptime_total);
        $this->assertSame(1, $dailyResult->downtime_total);
        $this->assertSame(1, $dailyResult->unknown_total);
        $this->assertSame(959, $dailyResult->uptime_minutes + $dailyResult->downtime_minutes);
        $this->assertSame(1439, $dailyResult->uptime_minutes + $dailyResult->downtime_minutes + $dailyResult->unknown_minutes);
        $this->assertEqualsWithDelta((480 / 1439) * 100, (float) $dailyResult->uptime_percentage, 0.0001);
        $this->assertEqualsWithDelta((479 / 1439) * 100, (float) $dailyResult->downtime_percentage, 0.0001);
        $this->assertEqualsWithDelta((480 / 1439) * 100, (float) $dailyResult->unknown_percentage, 0.0001);
    }
}
