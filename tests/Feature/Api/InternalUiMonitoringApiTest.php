<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalUiMonitoringApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_verified_user_can_list_only_visible_monitorings_with_a_bounded_page(): void
    {
        $user = User::factory()->create();
        $visibleMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Visible API']);
        Monitoring::factory()->for($user)->create(['name' => 'Another API']);
        Monitoring::factory()->create(['name' => 'Hidden API']);

        MonitoringResponse::query()->create([
            'monitoring_id' => $visibleMonitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 128.4,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.index', [
            'per_page' => 1,
            'search' => 'Visible',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $visibleMonitoring->id)
            ->assertJsonPath('data.0.latest_check.status', MonitoringStatus::UP->value)
            ->assertJsonPath('data.0.latest_check.response_time_ms', 128.4)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'target',
                    'type',
                    'lifecycle_status',
                    'groups',
                    'latest_check',
                    'open_incident',
                    'maintenance',
                ]],
                'meta' => ['as_of'],
            ])
            ->assertJsonMissing(['name' => 'Hidden API']);
    }

    public function test_guest_cannot_read_the_internal_ui_monitoring_projections(): void
    {
        $monitoring = Monitoring::factory()->create();

        $this->getJson(route('api.v1.internal.ui.monitorings.index'))
            ->assertUnauthorized();
        $this->getJson(route('api.v1.internal.ui.monitorings.show', $monitoring))
            ->assertUnauthorized();
        $this->getJson(route('api.v1.internal.ui.monitorings.cards', ['ids' => [$monitoring->id]]))
            ->assertUnauthorized();
    }

    public function test_internal_ui_monitoring_detail_returns_not_found_for_another_users_monitoring(): void
    {
        $user = User::factory()->create();
        $foreignMonitoring = Monitoring::factory()->create();

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.show', $foreignMonitoring))
            ->assertNotFound();
    }

    public function test_internal_ui_monitoring_cards_are_scoped_and_require_a_verified_session(): void
    {
        $user = User::factory()->create();
        $visibleMonitoring = Monitoring::factory()->for($user)->create();
        $foreignMonitoring = Monitoring::factory()->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $visibleMonitoring->id,
            'status' => MonitoringStatus::UP,
            'response_time' => 128.4,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.monitorings.cards', [
            'ids' => [$visibleMonitoring->id, $foreignMonitoring->id],
        ]))
            ->assertOk()
            ->assertJsonPath('data.' . $visibleMonitoring->id . '.status', MonitoringStatus::UP->value)
            ->assertJsonMissingPath('data.' . $foreignMonitoring->id);

        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)->getJson(route('api.v1.internal.ui.monitorings.index'))
            ->assertForbidden();
    }
}
