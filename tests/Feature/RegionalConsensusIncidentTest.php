<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringStatus;
use App\Enums\RegionalConsensusStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class RegionalConsensusIncidentTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_multi_location_monitoring_opens_and_closes_incident_by_majority_consensus(): void
    {
        Date::setTestNow('2026-07-12 12:00:00');
        Package::factory()->create();
        $user = User::factory()->create();
        $locations = collect(['consensus-de-1', 'consensus-us-1', 'consensus-sg-1'])
            ->map(fn (string $code): ServerInstance => $this->serverInstance($code));
        $monitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $locations[0]->code,
            'preferred_locations' => $locations->pluck('code')->all(),
            'failure_confirmation_threshold' => 1,
        ]);

        $this->storeResponse($locations[0], $monitoring, MonitoringStatus::DOWN);

        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'location_code' => $locations[0]->code,
        ]);
        $this->assertDatabaseCount('incidents', 0);
        $this->assertDatabaseCount('monitoring_notifications', 0);

        $testResponse = $this->actingAs($user)
            ->getJson('/api/v1/monitorings/' . $monitoring->id . '/status');
        $testResponse->assertOk()
            ->assertJsonPath('status', MonitoringStatus::UNKNOWN->value)
            ->assertJsonPath('regional_consensus.status', RegionalConsensusStatus::LOCALIZED->value)
            ->assertJsonPath('regional_consensus.required_failures', 2)
            ->assertJsonPath('regional_consensus.affected_locations.0', $locations[0]->code);

        $this->storeResponse($locations[1], $monitoring, MonitoringStatus::DOWN);

        $incident = Incident::query()->where('monitoring_id', $monitoring->id)->firstOrFail();
        $this->assertSame(RegionalConsensusStatus::REGIONAL, $incident->consensus_status);
        $this->assertEqualsCanonicalizing([$locations[0]->code, $locations[1]->code], $incident->affected_locations);
        $this->assertNull($incident->up_at);
        $this->assertDatabaseCount('monitoring_notifications', 1);

        $this->storeResponse($locations[2], $monitoring, MonitoringStatus::DOWN);

        $incident->refresh();
        $this->assertSame(RegionalConsensusStatus::GLOBAL, $incident->consensus_status);
        $this->assertCount(3, $incident->affected_locations);
        $this->assertDatabaseCount('monitoring_notifications', 1);

        $this->storeResponse($locations[0], $monitoring, MonitoringStatus::UP);
        $this->storeResponse($locations[1], $monitoring, MonitoringStatus::UP);

        $incident->refresh();
        $this->assertNotNull($incident->up_at);
        $this->assertDatabaseCount('monitoring_notifications', 2);
    }

    public function test_monitoring_detail_shows_location_consensus_matrix(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $serverInstance = $this->serverInstance('matrix-de-1');
        $second = $this->serverInstance('matrix-us-1');
        $monitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $serverInstance->code,
            'preferred_locations' => [$serverInstance->code, $second->code],
        ]);

        $this->storeResponse($serverInstance, $monitoring, MonitoringStatus::UP);
        $this->storeResponse($second, $monitoring, MonitoringStatus::DOWN);

        $this->actingAs($user)
            ->get(route('monitorings.show', $monitoring))
            ->assertOk()
            ->assertSeeText(__('monitoring.detail.regional_consensus.heading'))
            ->assertSeeText(__('monitoring.detail.regional_consensus.statuses.localized'))
            ->assertSeeText($serverInstance->code)
            ->assertSeeText($second->code);
    }

    private function storeResponse(ServerInstance $serverInstance, Monitoring $monitoring, MonitoringStatus $monitoringStatus): void
    {
        $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'test-token-1234567890',
        ])->postJson(route('v1.internal.monitoring-responses.store'), [
            'monitoring_id' => $monitoring->id,
            'status' => $monitoringStatus->value,
            'response_time' => 100,
        ])->assertOk();
    }

    private function serverInstance(string $code): ServerInstance
    {
        return ServerInstance::query()->create([
            'code' => $code,
            'ip_address' => '192.0.2.60',
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }
}
