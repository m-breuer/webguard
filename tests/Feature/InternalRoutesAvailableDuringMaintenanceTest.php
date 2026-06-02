<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalRoutesAvailableDuringMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private MaintenanceMode $maintenanceMode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maintenanceMode = new class implements MaintenanceMode
        {
            private ?array $payload = null;

            public function activate(array $payload): void
            {
                $this->payload = $payload;
            }

            public function deactivate(): void
            {
                $this->payload = null;
            }

            public function active(): bool
            {
                return $this->payload !== null;
            }

            public function data(): array
            {
                return $this->payload ?? [];
            }
        };
        $this->app->instance(MaintenanceMode::class, $this->maintenanceMode);

        config()->set('imprint.operator_name', 'Max Mustermann');
        config()->set('imprint.street', 'Musterstrasse 1');
        config()->set('imprint.postal_code', '10115');
        config()->set('imprint.city', 'Berlin');
        config()->set('imprint.country', 'Germany');
        config()->set('imprint.email', 'max@example.test');
        config()->set('imprint.phone', '+49 1512 3456789');
    }

    protected function tearDown(): void
    {
        $this->maintenanceMode->deactivate();
        $this->app->forgetInstance(MaintenanceMode::class);

        parent::tearDown();
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

        $this->maintenanceMode->activate(['time' => time()]);

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
            $this->maintenanceMode->deactivate();
        }
    }
}
