<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringStatusPayload;
use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringStatusPayloadService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringStatusPayloadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_status_payload_includes_status_metadata_and_monitoring_summary(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring([
            'name' => 'Primary API',
            'target' => 'https://example.com',
            'created_at' => Date::parse('2026-04-12 09:00:00'),
        ]);

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => null,
            'created_at' => Date::parse('2026-04-12 11:55:00'),
            'updated_at' => Date::parse('2026-04-12 11:55:00'),
        ]);

        $freshMonitoring = $monitoring->fresh();
        $statusPayload = app(MonitoringStatusPayloadService::class)->getPayload($freshMonitoring);
        $payload = $statusPayload->toArray();

        $this->assertInstanceOf(MonitoringStatusPayload::class, $statusPayload);
        $this->assertSame(MonitoringStatus::DOWN, $payload['status']);
        $this->assertSame(503, $payload['status_code']);
        $this->assertSame('status.server_error', $payload['status_identifier']);
        $this->assertSame('notifications.status.server_error', $payload['status_key']);
        $this->assertNotNull($payload['status_changed_at']);
        $this->assertSame('Primary API', $payload['monitoring']['name']);
        $this->assertSame('https://example.com', $payload['monitoring']['target']);
    }

    public function test_status_payload_can_omit_monitoring_summary_for_embedded_payloads(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring();

        $statusPayload = app(MonitoringStatusPayloadService::class)->getPayload($monitoring, includeMonitoring: false);
        $payload = $statusPayload->toArray();

        $this->assertInstanceOf(MonitoringStatusPayload::class, $statusPayload);
        $this->assertArrayNotHasKey('monitoring', $payload);
        $this->assertSame(MonitoringStatus::UNKNOWN->value, $payload['status']);
        $this->assertSame('status.unknown', $payload['status_identifier']);
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
}
