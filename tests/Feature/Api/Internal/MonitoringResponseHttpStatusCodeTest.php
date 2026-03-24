<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Internal;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringResponseHttpStatusCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_monitoring_response_stores_http_status_code(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $serverInstance->code,
        ]);

        $response = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->postJson(route('v1.internal.monitoring-responses.store'), [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
            'response_time' => 210.7,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
        ]);
    }

    public function test_internal_monitoring_response_rejects_invalid_http_status_code(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $serverInstance->code,
        ]);

        $response = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->postJson(route('v1.internal.monitoring-responses.store'), [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP->value,
            'http_status_code' => 99,
            'response_time' => 90.3,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['http_status_code']);
    }
}
