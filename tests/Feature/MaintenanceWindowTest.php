<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceWindowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Monitoring $monitoring;

    protected function setUp(): void
    {
        parent::setUp();
        Package::factory()->create();
        $this->user = User::factory()->create();
        $this->monitoring = Monitoring::factory()->create(['user_id' => $this->user->id]);
    }

    private function getValidData(): array
    {
        return [
            'name' => $this->monitoring->name,
            'type' => $this->monitoring->type->value,
            'target' => $this->monitoring->target,
            'status' => $this->monitoring->status->value,
            'preferred_location' => $this->monitoring->preferred_location->value,
        ];
    }

    public function test_api_returns_correct_maintenance_active_value()
    {
        config(['app.webguard_instance_api_key' => 'test-token']);
        // No maintenance window
        $response = $this->withHeaders([
            'X-API-KEY' => 'test-token',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location->value]));
        $response->assertJsonFragment(['maintenance_active' => false]);

        // Future maintenance window
        $this->monitoring->update(array_merge($this->getValidData(), [
            'maintenance_from' => now()->addHour(),
            'maintenance_until' => now()->addHours(2),
        ]));
        $response = $this->withHeaders([
            'X-API-KEY' => 'test-token',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location->value]));
        $response->assertJsonFragment(['maintenance_active' => false]);

        // Active maintenance window
        $this->monitoring->update(array_merge($this->getValidData(), [
            'maintenance_from' => now()->subHour(),
            'maintenance_until' => now()->addHour(),
        ]));
        $response = $this->withHeaders([
            'X-API-KEY' => 'test-token',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location->value]));
        $response->assertJsonFragment(['maintenance_active' => true]);

        // Open-ended maintenance window
        $this->monitoring->update(array_merge($this->getValidData(), [
            'maintenance_from' => now()->subHour(),
            'maintenance_until' => null,
        ]));
        $response = $this->withHeaders([
            'X-API-KEY' => 'test-token',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $this->monitoring->preferred_location->value]));
        $response->assertJsonFragment(['maintenance_active' => true]);
    }
}
