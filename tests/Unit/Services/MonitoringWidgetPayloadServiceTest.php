<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringWidgetPayload;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringWidgetPayloadService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringWidgetPayloadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_widget_payload_contains_public_status_metadata_and_uptime_ranges(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subDays(10),
        ]);

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ]);

        $this->createDailyResult($monitoring, Date::now()->subDays(2)->toDateString());

        $monitoringWidgetPayload = resolve(MonitoringWidgetPayloadService::class)->getPayload($monitoring->fresh());
        $payload = $monitoringWidgetPayload->toArray();

        $this->assertInstanceOf(MonitoringWidgetPayload::class, $monitoringWidgetPayload);
        $this->assertSame('Primary API', $payload['name']);
        $this->assertSame(MonitoringStatus::UP->value, $payload['status']);
        $this->assertSame('UP', $payload['status_label']);
        $this->assertSame(200, $payload['status_code']);
        $this->assertSame('status.success', $payload['status_identifier']);
        $this->assertSame(route('public-label', $monitoring), $payload['public_url']);
        $this->assertEquals(100.0, $payload['uptime']['7_days']);
        $this->assertEquals(100.0, $payload['uptime']['30_days']);
        $this->assertEquals(100.0, $payload['uptime']['365_days']);
    }

    public function test_widget_payload_returns_unknown_without_results(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring([
            'name' => 'Fresh API',
            'public_label_enabled' => true,
            'created_at' => Date::now()->subMinutes(30),
        ]);

        $monitoringWidgetPayload = resolve(MonitoringWidgetPayloadService::class)->getPayload($monitoring);
        $payload = $monitoringWidgetPayload->toArray();

        $this->assertInstanceOf(MonitoringWidgetPayload::class, $monitoringWidgetPayload);
        $this->assertSame('Fresh API', $payload['name']);
        $this->assertSame(MonitoringStatus::UNKNOWN->value, $payload['status']);
        $this->assertSame('UNKNOWN', $payload['status_label']);
        $this->assertNull($payload['checked_at']);
        $this->assertNull($payload['checked_at_human']);
        $this->assertNull($payload['uptime']['7_days']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMonitoring(array $attributes = []): Monitoring
    {
        Package::factory()->create();
        $user = User::factory()->create();

        return Monitoring::factory()->for($user)->create($attributes);
    }

    private function createDailyResult(Monitoring $monitoring, string $date): void
    {
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
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
    }
}
