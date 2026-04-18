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

        $defaultResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $serverInstance->code]));

        $defaultResponse->assertOk();
        $defaultResponse->assertJsonFragment(['id' => $httpMonitoring->id]);
        $defaultResponse->assertJsonMissing(['id' => $heartbeatMonitoring->id]);

        $heartbeatResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', [
            'location' => $serverInstance->code,
            'type' => MonitoringType::HEARTBEAT->value,
        ]));

        $heartbeatResponse->assertOk();
        $heartbeatResponse->assertJsonFragment(['id' => $heartbeatMonitoring->id]);
        $heartbeatResponse->assertJsonFragment(['heartbeat_interval_minutes' => 60]);
        $heartbeatResponse->assertJsonFragment(['heartbeat_grace_minutes' => 10]);
    }
}
