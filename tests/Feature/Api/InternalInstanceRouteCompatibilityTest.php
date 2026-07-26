<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

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

    public function test_legacy_and_target_instance_routes_use_the_same_actions(): void
    {
        foreach ($this->routeNames() as $legacyName => $targetName) {
            $legacyRoute = Route::getRoutes()->getByName($legacyName);
            $targetRoute = Route::getRoutes()->getByName($targetName);

            $this->assertNotNull($legacyRoute);
            $this->assertNotNull($targetRoute);
            $this->assertSame($legacyRoute->methods(), $targetRoute->methods());
            $this->assertSame($legacyRoute->getActionName(), $targetRoute->getActionName());
            $this->assertStringStartsWith('api/v1/internal/instances/', $targetRoute->uri());
        }
    }

    public function test_target_monitoring_list_enforces_the_existing_instance_contract(): void
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
        ])->getJson(route('v1.internal.instances.monitorings.list', ['location' => $serverInstance->code]))
            ->assertOk()
            ->assertJsonPath('0.id', $assignedMonitoring->id)
            ->assertJsonCount(1);
    }

    public function test_legacy_and_target_instance_routes_keep_the_same_authentication_and_location_contract(): void
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

        foreach ([
            'v1.internal.monitorings.list',
            'v1.internal.instances.monitorings.list',
        ] as $routeName) {
            $this->flushHeaders();

            $this->getJson(route($routeName, ['location' => $serverInstance->code]))
                ->assertUnauthorized()
                ->assertJsonPath('message', 'Unauthorized');

            $this->withHeaders($this->instanceHeaders($serverInstance))
                ->getJson(route($routeName, ['location' => 'different-instance']))
                ->assertForbidden()
                ->assertJsonPath('message', 'Unauthorized location');
        }
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        return [
            'v1.internal.monitorings.list' => 'v1.internal.instances.monitorings.list',
            'v1.internal.monitoring-responses.store' => 'v1.internal.instances.monitoring-responses.store',
            'v1.internal.incidents.store' => 'v1.internal.instances.incidents.store',
            'v1.internal.incidents.update' => 'v1.internal.instances.incidents.update',
            'v1.internal.ssl-results.store' => 'v1.internal.instances.ssl-results.store',
            'v1.internal.domain-results.store' => 'v1.internal.instances.domain-results.store',
        ];
    }
}
