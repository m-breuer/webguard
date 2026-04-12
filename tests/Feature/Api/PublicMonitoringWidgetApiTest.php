<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class PublicMonitoringWidgetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_widget_endpoint_returns_public_monitoring_payload_without_authentication(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subDays(10),
        ]);

        $checkedAt = Date::now()->subMinutes(5);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'created_at' => $checkedAt,
            'updated_at' => $checkedAt,
        ]);

        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => Date::now()->subDays(2)->toDateString(),
            'uptime_total' => 1,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 100,
            'downtime_percentage' => 0,
            'unknown_percentage' => 0,
            'uptime_minutes' => 24 * 60,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => 123.4,
            'min_response_time' => 123.4,
            'max_response_time' => 123.4,
            'incidents_count' => 0,
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/widget');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('name', 'Primary API');
        $testResponse->assertJsonPath('status', MonitoringStatus::UP->value);
        $testResponse->assertJsonPath('status_label', 'UP');
        $testResponse->assertJsonPath('status_code', 200);
        $testResponse->assertJsonPath('status_identifier', 'status.success');
        $testResponse->assertJsonPath('public_url', route('public-label', $monitoring));
        $this->assertIsNumeric($testResponse->json('uptime.7_days'));
        $this->assertIsNumeric($testResponse->json('uptime.30_days'));
        $this->assertIsNumeric($testResponse->json('uptime.365_days'));
    }

    public function test_public_widget_endpoint_returns_not_found_when_public_label_is_disabled(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/widget');

        $testResponse->assertNotFound();
    }
}
