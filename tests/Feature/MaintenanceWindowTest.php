<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceWindowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Monitoring $monitoring;

    private ServerInstance $serverInstance;

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

        Package::factory()->create();
        $this->user = User::factory()->create();
        $this->serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $this->serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
        $this->monitoring = Monitoring::factory()->create(['user_id' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        $this->maintenanceMode->deactivate();
        $this->app->forgetInstance(MaintenanceMode::class);

        parent::tearDown();
    }

    public function test_internal_monitoring_api_is_available_during_application_maintenance(): void
    {
        $this->maintenanceMode->activate(['time' => time()]);

        $testResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])
            ->getJson(route('v1.internal.monitorings.list', ['location' => $this->serverInstance->code]));

        $this->maintenanceMode->deactivate();

        $testResponse
            ->assertOk()
            ->assertJsonFragment(['id' => $this->monitoring->id]);
    }

    public function test_api_returns_correct_maintenance_active_value()
    {
        // No maintenance window
        $response = $this->withHeaders([
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location]));
        $response->assertJsonFragment(['maintenance_active' => false]);

        // Future maintenance window
        $this->monitoring->update(array_merge($this->getValidData(), [
            'maintenance_from' => now()->addHour(),
            'maintenance_until' => now()->addHours(2),
        ]));
        $response = $this->withHeaders([
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location]));
        $response->assertJsonFragment(['maintenance_active' => false]);

        // Active maintenance window
        $this->monitoring->update(array_merge($this->getValidData(), [
            'maintenance_from' => now()->subHour(),
            'maintenance_until' => now()->addHour(),
        ]));
        $response = $this->withHeaders([
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location]));
        $response->assertJsonFragment(['maintenance_active' => true]);

        // Open-ended maintenance window
        $this->monitoring->update(array_merge($this->getValidData(), [
            'maintenance_from' => now()->subHour(),
            'maintenance_until' => null,
        ]));
        $response = $this->withHeaders([
            'X-INSTANCE-CODE' => $this->serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location]));
        $response->assertJsonFragment(['maintenance_active' => true]);
    }

    private function getValidData(): array
    {
        return [
            'name' => $this->monitoring->name,
            'type' => $this->monitoring->type->value,
            'target' => $this->monitoring->target,
            'status' => $this->monitoring->status->value,
            'preferred_location' => $this->monitoring->preferred_location,
        ];
    }
}
