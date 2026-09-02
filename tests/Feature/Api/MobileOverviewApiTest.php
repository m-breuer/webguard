<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_read_the_operations_overview(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create();
        $healthy = Monitoring::factory()->for($user)->create(['name' => 'Healthy API']);
        $down = Monitoring::factory()->for($user)->create(['name' => 'Down API']);
        Monitoring::factory()->for($user)->create(['name' => 'Unknown API']);

        MonitoringResponse::query()->create([
            'monitoring_id' => $healthy->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 120,
        ]);
        MonitoringResponse::query()->create([
            'monitoring_id' => $down->id,
            'status' => MonitoringStatus::DOWN,
            'response_time' => 900,
        ]);
        $incident = Incident::query()->create([
            'monitoring_id' => $down->id,
            'down_at' => Date::now()->subMinutes(5),
        ]);
        Sanctum::actingAs($user);

        $testResponse = $this->getJson('/api/mobile/overview');

        $testResponse
            ->assertOk()
            ->assertJsonPath('data.overall_state', 'degraded')
            ->assertJsonPath('data.summary.total', 3)
            ->assertJsonPath('data.summary.healthy', 1)
            ->assertJsonPath('data.summary.down', 1)
            ->assertJsonPath('data.summary.unknown', 1)
            ->assertJsonPath('data.services.0.name', 'Down API')
            ->assertJsonPath('data.services.0.open_incident', true)
            ->assertJsonPath('data.attention.0.type', 'incident')
            ->assertJsonPath('data.attention.0.monitoring_id', $down->id)
            ->assertJsonPath('data.recent_incidents.0.id', $incident->id)
            ->assertJsonPath('meta.service_pagination.total', 3);
    }

    public function test_overview_only_contains_monitorings_visible_to_the_authenticated_user(): void
    {
        Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create();
        Monitoring::factory()->for($user)->create(['name' => 'Visible API']);
        Monitoring::factory()->create(['name' => 'Hidden API']);
        Sanctum::actingAs($user);

        $testResponse = $this->getJson('/api/mobile/overview');

        $testResponse->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.services.0.name', 'Visible API')
            ->assertJsonMissing(['name' => 'Hidden API']);
    }

    public function test_overview_supports_bounded_service_pagination(): void
    {
        Package::factory()->create(['monitoring_limit' => 20]);
        $user = User::factory()->create();

        foreach (range(1, 12) as $index) {
            Monitoring::factory()->for($user)->create(['name' => sprintf('API %02d', $index)]);
        }

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/overview?service_page=2')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 12)
            ->assertJsonPath('meta.service_pagination.current_page', 2)
            ->assertJsonPath('meta.service_pagination.last_page', 2)
            ->assertJsonCount(2, 'data.services');
    }
}
