<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InternalRoutesAvailableDuringMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_routes_remain_accessible_in_maintenance_mode(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $instance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $instance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);

        $monitoring = Monitoring::factory()->create([
            'user_id' => $user->id,
            'type' => MonitoringType::HTTP,
            'preferred_location' => $instance->code,
        ]);

        Artisan::call('down');

        try {
            $internalV1Response = $this->withHeaders([
                'X-INSTANCE-CODE' => $instance->code,
                'X-API-KEY' => 'test-token-1234567890',
            ])->getJson(route('v1.internal.monitorings.list', ['location' => $instance->code]));

            $internalV1Response->assertStatus(200);

            $legacyInternalResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');
            $legacyInternalResponse->assertStatus(200);
        } finally {
            Artisan::call('up');
        }
    }
}
