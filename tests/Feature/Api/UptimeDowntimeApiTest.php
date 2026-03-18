<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class UptimeDowntimeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_neutral_state_for_monitoring_without_any_results(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subMinutes(30),
        ]);

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/uptime-downtime?days=7');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('has_data', false);
        $testResponse->assertJsonPath('tracking_started_at', null);
        $testResponse->assertJsonPath('uptime.percentage', null);
        $testResponse->assertJsonPath('downtime.percentage', null);
    }

    public function test_uses_only_actual_tracked_window_for_partial_history_in_selected_period(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subDays(5),
        ]);

        $trackingStartedAt = Date::now()->subHour();

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

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/uptime-downtime?days=7');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('has_data', true);
        $testResponse->assertJsonPath('tracking_started_at', $trackingStartedAt->toIso8601String());
        $this->assertSame(0.0, (float) $testResponse->json('uptime.percentage'));
        $this->assertSame(100.0, (float) $testResponse->json('downtime.percentage'));
    }

    public function test_long_running_monitoring_keeps_existing_period_math(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subDays(10),
        ]);

        $trackingStartedAt = Date::now()->subDays(7)->startOfDay();

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 120,
            'created_at' => $trackingStartedAt,
            'updated_at' => $trackingStartedAt,
        ]);

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDays(2)->setTime(10, 0),
            'up_at' => Date::now()->subDays(2)->setTime(11, 0),
        ]);

        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => Date::now()->subDays(2)->toDateString(),
            'uptime_total' => 1,
            'downtime_total' => 1,
            'uptime_percentage' => ((24 * 60 - 60) / (24 * 60)) * 100,
            'downtime_percentage' => (60 / (24 * 60)) * 100,
            'uptime_minutes' => (24 * 60) - 60,
            'downtime_minutes' => 60,
            'avg_response_time' => 120,
            'min_response_time' => 120,
            'max_response_time' => 120,
            'incidents_count' => 1,
        ]);

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/uptime-downtime?days=7');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('has_data', true);

        $expectedTrackedMinutes = (24 * 60) + Date::today()->diffInMinutes(Date::now());
        $expectedUptimePercentage = (($expectedTrackedMinutes - 60) / $expectedTrackedMinutes) * 100;

        $this->assertEqualsWithDelta($expectedUptimePercentage, (float) $testResponse->json('uptime.percentage'), 0.0001);
    }
}
