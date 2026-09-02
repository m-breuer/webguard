<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Internal;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class HeartbeatMonitoringListCompatibilityTest extends TestCase
{
    public function test_internal_monitoring_list_excludes_heartbeat_monitorings_by_default_but_allows_explicit_filter(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);

        $httpMonitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $serverInstance->code,
            'status' => MonitoringLifecycleStatus::ACTIVE,
        ]);

        $heartbeatMonitoring = Monitoring::factory()->heartbeat()->for($user)->create([
            'preferred_location' => $serverInstance->code,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'heartbeat_token' => 'heartbeat-token',
            'target' => route('monitorings.heartbeat.ping', ['token' => 'heartbeat-token']),
        ]);

        $serverHealthMonitoring = Monitoring::factory()->serverHealth()->for($user)->create([
            'preferred_location' => $serverInstance->code,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'server_health_token' => 'server-health-token',
            'target' => route('server-health.store', ['token' => 'server-health-token']),
            'server_health_cpu_threshold_percent' => 85,
            'server_health_ram_threshold_percent' => 80,
            'server_health_storage_threshold_percent' => 95,
        ]);

        $testResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('instances.monitorings.list', ['location' => $serverInstance->code]));

        $testResponse->assertOk();
        $testResponse->assertJsonFragment(['id' => $httpMonitoring->id]);
        $testResponse->assertJsonMissing(['id' => $heartbeatMonitoring->id]);
        $testResponse->assertJsonMissing(['id' => $serverHealthMonitoring->id]);

        $heartbeatResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('instances.monitorings.list', [
            'location' => $serverInstance->code,
            'type' => MonitoringType::HEARTBEAT->value,
        ]));

        $heartbeatResponse->assertOk();
        $heartbeatResponse->assertJsonFragment(['id' => $heartbeatMonitoring->id]);
        $heartbeatResponse->assertJsonFragment(['heartbeat_interval_minutes' => 60]);
        $heartbeatResponse->assertJsonFragment(['heartbeat_grace_minutes' => 10]);

        $serverHealthResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('instances.monitorings.list', [
            'location' => $serverInstance->code,
            'type' => MonitoringType::SERVER_HEALTH->value,
        ]));

        $serverHealthResponse->assertOk();
        $serverHealthResponse->assertJsonFragment(['id' => $serverHealthMonitoring->id]);
        $serverHealthResponse->assertJsonFragment(['server_health_cpu_threshold_percent' => 85]);
        $serverHealthResponse->assertJsonFragment(['server_health_ram_threshold_percent' => 80]);
        $serverHealthResponse->assertJsonFragment(['server_health_storage_threshold_percent' => 95]);
    }
}
