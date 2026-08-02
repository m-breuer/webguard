<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringPerformanceStatus;
use App\Enums\MonitoringStatus;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use App\Services\MonitoringHealthEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDerivedHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_instance_accepts_raw_http_evidence_without_persisting_a_status(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $serverInstance = $this->serverInstance();
        $monitoring = Monitoring::factory()->for($user)->create([
            'type' => 'http',
            'preferred_location' => $serverInstance->code,
            'expected_http_statuses' => '200-299',
        ]);

        $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->postJson(route('v1.internal.monitoring-responses.store'), [
            'monitoring_id' => $monitoring->id,
            'http_status_code' => 204,
            'response_time' => 120.5,
            'vital_values' => ['transport_succeeded' => true],
        ])->assertOk();

        $monitoringResponse = MonitoringResponse::query()->sole();

        $this->assertNull($monitoringResponse->status);
        $this->assertSame(MonitoringStatus::UP, resolve(MonitoringHealthEvaluator::class)->availabilityFor($monitoring, $monitoringResponse));
    }

    public function test_raw_transport_failure_opens_an_availability_incident(): void
    {
        Package::factory()->create();
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'type' => 'http',
            'failure_confirmation_threshold' => 1,
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'vital_values' => [
                'transport_succeeded' => false,
                'failure_reason' => 'connection timeout',
            ],
        ]);

        $this->assertDatabaseHas('incidents', ['monitoring_id' => $monitoring->id, 'up_at' => null]);
    }

    public function test_slow_successful_responses_create_one_degraded_event_and_a_recovery_event(): void
    {
        Package::factory()->create();
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'type' => 'http',
            'response_time_threshold_ms' => 250,
            'response_time_confirmation_threshold' => 2,
        ]);

        $this->storeHttpResponse($monitoring, 300.0);
        $this->assertDatabaseHas('monitoring_performance_states', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringPerformanceStatus::NORMAL->value,
            'consecutive_breaches' => 1,
        ]);
        $this->assertDatabaseCount('monitoring_notifications', 0);

        $this->storeHttpResponse($monitoring, 300.0);
        $this->assertDatabaseHas('monitoring_performance_states', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringPerformanceStatus::DEGRADED->value,
            'consecutive_breaches' => 2,
        ]);
        $this->assertDatabaseHas('monitoring_notifications', [
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::PERFORMANCE->value,
            'message' => 'DEGRADED',
        ]);

        $this->storeHttpResponse($monitoring, 200.0);

        $this->assertDatabaseHas('monitoring_performance_states', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringPerformanceStatus::NORMAL->value,
            'consecutive_breaches' => 0,
        ]);
        $this->assertDatabaseHas('monitoring_notifications', [
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::PERFORMANCE->value,
            'message' => 'RECOVERED',
        ]);
        $this->assertSame(2, MonitoringNotification::query()->performance()->count());
        $this->assertDatabaseCount('incidents', 0);
    }

    private function storeHttpResponse(Monitoring $monitoring, float $responseTime): void
    {
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'http_status_code' => 200,
            'response_time' => $responseTime,
            'vital_values' => ['transport_succeeded' => true],
        ]);
    }

    private function serverInstance(): ServerInstance
    {
        return ServerInstance::query()->create([
            'code' => 'raw-evidence-de-1',
            'ip_address' => '192.0.2.10',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }
}
