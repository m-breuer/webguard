<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

class InternalInstanceRouteCompatibilityTest extends TestCase
{
    use AssertsApiContracts;

    public function test_instance_routes_use_the_dedicated_instances_namespace(): void
    {
        foreach ($this->routeNames() as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertStringStartsWith('api/instances/', $route->uri());
            $this->assertStringNotContainsString('/v1/', $route->uri());
            $this->assertStringNotContainsString('/internal/', $route->uri());
        }
    }

    public function test_instance_monitoring_list_enforces_the_existing_contract(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $serverInstance = ServerInstance::query()->create([
            'code' => 'target-route-instance',
            'ip_address' => '192.0.2.56',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
        $assignedMonitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::HTTP,
            'preferred_location' => $serverInstance->code,
            'preferred_locations' => [$serverInstance->code],
        ]);
        Monitoring::factory()->for($user)->create([
            'preferred_location' => 'another-instance',
            'preferred_locations' => ['another-instance'],
        ]);

        $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->getJson(route('instances.monitorings.list', ['location' => $serverInstance->code]))
            ->assertOk()
            ->assertJsonPath('0.id', $assignedMonitoring->id)
            ->assertJsonPath('0.check_interval_seconds', 900)
            ->assertJsonCount(1);
    }

    public function test_instance_routes_enforce_the_authentication_and_location_contract(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $serverInstance = ServerInstance::query()->create([
            'code' => 'contract-instance',
            'ip_address' => '192.0.2.57',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
        Monitoring::factory()->for($user)->create([
            'preferred_location' => $serverInstance->code,
            'preferred_locations' => [$serverInstance->code],
        ]);

        $this->getJson(route('instances.monitorings.list', ['location' => $serverInstance->code]))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized');

        $this->withHeaders($this->instanceHeaders($serverInstance))
            ->getJson(route('instances.monitorings.list', ['location' => 'different-instance']))
            ->assertForbidden()
            ->assertJsonPath('message', 'Unauthorized location');
    }

    /**
     * @return list<string>
     */
    private function routeNames(): array
    {
        return [
            'instances.monitorings.list',
            'instances.monitoring-responses.store',
            'instances.incidents.store',
            'instances.incidents.update',
            'instances.ssl-results.store',
            'instances.domain-results.store',
        ];
    }
}
