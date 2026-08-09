<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use Tests\TestCase;

class MonitoringDataAccessTest extends TestCase
{
    public function test_shared_monitoring_data_endpoint_requires_authentication_for_private_monitoring(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertUnauthorized();
    }

    public function test_shared_monitoring_data_endpoint_requires_authentication_for_public_monitoring(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Public Monitoring',
            'public_label_enabled' => true,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertUnauthorized();
    }

    public function test_shared_monitoring_data_endpoint_allows_private_monitoring_for_owner(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Private API',
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertOk()
            ->assertJsonPath('monitoring.name', 'Private API');
    }

    public function test_shared_monitoring_data_endpoint_blocks_private_monitoring_for_another_user(): void
    {
        Package::factory()->create();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $monitoring = Monitoring::factory()->for($owner)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->actingAs($otherUser)->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertNotFound();
    }

    public function test_validation_errors_do_not_leak_private_monitoring_existence(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/uptime-calendar');

        $testResponse->assertUnauthorized();
    }

    public function test_server_health_telemetry_is_available_only_to_the_monitoring_owner(): void
    {
        Package::factory()->create();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $monitoring = Monitoring::factory()->for($owner)->create([
            'type' => MonitoringType::SERVER_HEALTH,
            'server_health_cpu_threshold_percent' => 80,
        ]);
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'server_health_metrics' => [
                'cpu_usage_percent' => 42.5,
                'ram_usage_percent' => 68.2,
                'storage_usage_percent' => 74.1,
                'load_average_1m' => 2,
                'logical_cpu_count' => 2,
            ],
        ]);

        $this->actingAs($owner)
            ->getJson('/api/monitorings/' . $monitoring->id . '/server-health-telemetry?days=1')
            ->assertOk()
            ->assertJsonPath('data.0.cpu_usage_percent', 42.5)
            ->assertJsonPath('data.0.normalized_load', 1)
            ->assertJsonPath('thresholds.cpu_usage_percent', 80);

        $this->actingAs($otherUser)
            ->getJson('/api/monitorings/' . $monitoring->id . '/server-health-telemetry?days=1')
            ->assertNotFound();
    }
}
