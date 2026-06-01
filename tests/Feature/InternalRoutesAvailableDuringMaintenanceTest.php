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

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('imprint.operator_name', 'Max Mustermann');
        config()->set('imprint.street', 'Musterstrasse 1');
        config()->set('imprint.postal_code', '10115');
        config()->set('imprint.city', 'Berlin');
        config()->set('imprint.country', 'Germany');
        config()->set('imprint.email', 'max@example.test');
        config()->set('imprint.phone', '+49 1512 3456789');
    }

    public function test_internal_routes_remain_accessible_in_maintenance_mode(): void
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

        $monitoring = Monitoring::factory()->create([
            'user_id' => $user->id,
            'type' => MonitoringType::HTTP,
            'preferred_location' => $serverInstance->code,
            'public_label_enabled' => true,
        ]);

        Artisan::call('down');

        try {
            $internalV1Response = $this->withHeaders([
                'X-INSTANCE-CODE' => $serverInstance->code,
                'X-API-KEY' => 'test-token-1234567890',
            ])->getJson(route('v1.internal.monitorings.list', ['location' => $serverInstance->code]));

            $internalV1Response->assertOk();

            $legacyInternalResponse = $this->getJson('/api/monitorings/' . $monitoring->id . '/status');
            $legacyInternalResponse->assertOk();

            $gdprResponse = $this->get(route('gdpr'));
            $gdprResponse->assertOk();
        } finally {
            Artisan::call('up');
        }
    }
}
