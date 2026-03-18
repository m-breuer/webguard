<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class CustomRangeStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_neutral_state_when_monitoring_has_no_tracking_data(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subHours(2),
        ]);

        $testResponse = $this->actingAs($user)->getJson(
            '/api/v1/monitorings/' . $monitoring->id . '/custom-range-stats?from=2026-03-18&until=2026-03-18'
        );

        $testResponse->assertOk();
        $testResponse->assertJsonPath('has_data', false);
        $testResponse->assertJsonPath('uptime_percentage', null);
        $testResponse->assertJsonPath('tracking_started_at', null);
        $testResponse->assertJsonPath('incidents_count', 0);
    }

    public function test_multi_day_custom_range_uses_aggregated_data_only(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subDays(3),
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

        $testResponse = $this->actingAs($user)->getJson(
            '/api/v1/monitorings/' . $monitoring->id . '/custom-range-stats?from=2026-03-12&until=2026-03-18'
        );

        $testResponse->assertOk();
        $testResponse->assertJsonPath('has_data', false);
        $testResponse->assertJsonPath('tracking_started_at', null);
        $testResponse->assertJsonPath('uptime_percentage', null);
        $testResponse->assertJsonPath('incidents_count', 0);
    }

    public function test_intraday_custom_range_uses_raw_data(): void
    {
        Date::setTestNow('2026-03-18 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subDays(3),
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

        $testResponse = $this->actingAs($user)->getJson(
            '/api/v1/monitorings/' . $monitoring->id . '/custom-range-stats?from=2026-03-18&until=2026-03-18'
        );

        $testResponse->assertOk();
        $testResponse->assertJsonPath('has_data', true);
        $testResponse->assertJsonPath('tracking_started_at', $trackingStartedAt->toIso8601String());
        $testResponse->assertJsonPath('incidents_count', 1);
        $this->assertSame(0.0, (float) $testResponse->json('uptime_percentage'));
    }

    public function test_validates_custom_range_date_order(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $testResponse = $this->actingAs($user)->getJson(
            '/api/v1/monitorings/' . $monitoring->id . '/custom-range-stats?from=2026-02-10&until=2026-02-09'
        );

        $testResponse->assertUnprocessable();
        $testResponse->assertJsonValidationErrors(['until']);
    }
}
