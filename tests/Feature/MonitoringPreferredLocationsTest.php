<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class MonitoringPreferredLocationsTest extends TestCase
{
    private User $user;

    private ServerInstance $deInstance;

    private ServerInstance $usInstance;

    private ServerInstance $nlInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $this->user = User::factory()->create(['package_id' => $package->id]);

        $this->deInstance = $this->serverInstance('multi-de-1');
        $this->usInstance = $this->serverInstance('multi-us-1');
        $this->nlInstance = $this->serverInstance('multi-nl-1');
    }

    public function test_monitoring_can_be_created_with_multiple_preferred_locations(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'Multi Location Ping',
            'type' => MonitoringType::PING->value,
            'target' => '8.8.8.8',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_locations' => [
                $this->deInstance->code,
                $this->usInstance->code,
            ],
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Multi Location Ping')->firstOrFail();

        $this->assertSame($this->deInstance->code, $monitoring->preferred_location);
        $this->assertSame([$this->deInstance->code, $this->usInstance->code], $monitoring->preferred_locations);
        $this->assertSame([$this->deInstance->code, $this->usInstance->code], $monitoring->preferredLocationCodes());
    }

    public function test_internal_monitoring_list_returns_monitoring_for_each_selected_location(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->deInstance->code,
            'preferred_locations' => [$this->deInstance->code, $this->usInstance->code],
        ]);

        $testResponse = $this->withHeaders($this->instanceHeaders($this->deInstance))
            ->getJson(route('v1.internal.monitorings.list', ['location' => $this->deInstance->code]));
        $secondaryResponse = $this->withHeaders($this->instanceHeaders($this->usInstance))
            ->getJson(route('v1.internal.monitorings.list', ['location' => $this->usInstance->code]));
        $unassignedResponse = $this->withHeaders($this->instanceHeaders($this->nlInstance))
            ->getJson(route('v1.internal.monitorings.list', ['location' => $this->nlInstance->code]));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('0.id', $monitoring->id);
        $testResponse->assertJsonPath('0.preferred_location', $this->deInstance->code);
        $testResponse->assertJsonPath('0.preferred_locations.1', $this->usInstance->code);

        $secondaryResponse->assertOk();
        $secondaryResponse->assertJsonPath('0.id', $monitoring->id);
        $secondaryResponse->assertJsonPath('0.preferred_locations.0', $this->deInstance->code);

        $unassignedResponse->assertOk();
        $unassignedResponse->assertJsonMissing(['id' => $monitoring->id]);
    }

    public function test_secondary_location_can_store_monitoring_responses(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->deInstance->code,
            'preferred_locations' => [$this->deInstance->code, $this->usInstance->code],
        ]);

        $testResponse = $this->withHeaders($this->instanceHeaders($this->usInstance))
            ->postJson(route('v1.internal.monitoring-responses.store'), [
                'monitoring_id' => $monitoring->id,
                'status' => MonitoringStatus::UP->value,
                'response_time' => 123.4,
            ]);

        $testResponse->assertOk();
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP->value,
        ]);

        $deniedResponse = $this->withHeaders($this->instanceHeaders($this->nlInstance))
            ->postJson(route('v1.internal.monitoring-responses.store'), [
                'monitoring_id' => $monitoring->id,
                'status' => MonitoringStatus::DOWN->value,
                'response_time' => 321.0,
            ]);

        $deniedResponse->assertForbidden();
    }

    private function serverInstance(string $code): ServerInstance
    {
        return ServerInstance::query()->create([
            'code' => $code,
            'ip_address' => '192.0.2.10',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function instanceHeaders(ServerInstance $serverInstance): array
    {
        return [
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ];
    }
}
