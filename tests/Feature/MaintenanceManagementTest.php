<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\TeamRole;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MaintenanceManagementTest extends TestCase
{
    private User $user;

    private ServerInstance $serverInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $this->user = User::factory()->create(['package_id' => $package->id]);

        $this->serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
    }

    public function test_user_can_view_central_maintenance_page(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Checkout API',
            'target' => 'https://checkout.example.com',
            'preferred_location' => $this->serverInstance->code,
            'maintenance_from' => '2026-07-01 10:00:00',
            'maintenance_until' => '2026-07-01 11:00:00',
        ]);
        $group = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $monitoring->groups()->attach($group);

        $testResponse = $this->actingAs($this->user)->get(route('maintenance.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('maintenance.title'));
        $testResponse->assertDontSeeText('Checkout API');
        $testResponse->assertSeeHtml('x-data="maintenancePage');
        $testResponse->assertSeeHtml('action="' . route('maintenance.store') . '"');
    }

    public function test_maintenance_api_paginates_window_list_without_limiting_monitoring_options(): void
    {
        Monitoring::factory()->for($this->user)->create([
            'name' => 'Monitoring 000',
            'preferred_location' => $this->serverInstance->code,
        ]);

        foreach (range(1, 50) as $index) {
            Monitoring::factory()->for($this->user)->create([
                'name' => sprintf('Monitoring %03d', $index),
                'preferred_location' => $this->serverInstance->code,
            ]);
        }

        $lastMonitoring = Monitoring::query()->where('name', 'Monitoring 050')->firstOrFail();
        $testResponse = $this->actingAs($this->user)->getJson(route('api.maintenance.index'));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('data.windows.current_page', 1);
        $testResponse->assertJsonPath('data.windows.last_page', 2);
        $testResponse->assertJsonCount(50, 'data.windows.data');
        $testResponse->assertJsonFragment(['id' => (string) $lastMonitoring->id, 'name' => 'Monitoring 050']);
        $testResponse->assertJsonFragment(['name' => 'Monitoring 000']);
        $testResponse->assertJsonCount(51, 'data.monitoring_options');
    }

    public function test_maintenance_api_supports_filtering_search_and_sorting(): void
    {
        Date::setTestNow('2026-07-01 10:30:00');
        $this->beforeApplicationDestroyed(fn (): mixed => Date::setTestNow());

        $group = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $activeMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Alpha Maintenance',
            'target' => 'https://alpha-maintenance.example.test',
            'preferred_location' => $this->serverInstance->code,
            'maintenance_from' => '2026-07-01 10:00:00',
            'maintenance_until' => '2026-07-01 11:00:00',
        ]);
        $zuluMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Zulu Maintenance',
            'target' => 'https://zulu-maintenance.example.test',
            'preferred_location' => $this->serverInstance->code,
            'maintenance_from' => '2026-07-01 09:00:00',
            'maintenance_until' => '2026-07-01 12:00:00',
        ]);
        Monitoring::factory()->for($this->user)->create([
            'name' => 'Outside Maintenance',
            'target' => 'https://outside-maintenance.example.test',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $activeMonitoring->groups()->attach($group);
        $zuluMonitoring->groups()->attach($group);

        $testResponse = $this->actingAs($this->user)->getJson(route('api.maintenance.index', [
            'search' => 'Maintenance',
            'maintenance_status' => 'active',
            'monitoring_group_id' => $group->id,
            'sort' => 'name',
            'direction' => 'desc',
            'per_page' => 5,
        ]));

        $testResponse->assertOk()
            ->assertJsonPath('data.windows.total', 2)
            ->assertJsonPath('data.windows.data.0.name', 'Zulu Maintenance')
            ->assertJsonPath('data.windows.data.1.name', 'Alpha Maintenance');
        $this->assertNotContains('Outside Maintenance', array_column($testResponse->json('data.windows.data'), 'name'));
    }

    public function test_maintenance_actions_support_json_requests(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);

        $this->actingAs($this->user)->postJson(route('api.maintenance.store'), [
            'scope' => 'monitoring',
            'monitoring_id' => $monitoring->id,
            'maintenance_from' => '2026-07-01T10:00',
            'maintenance_until' => '2026-07-01T11:00',
        ])->assertOk()->assertJsonStructure(['message', 'updated_count']);

        $this->actingAs($this->user)->deleteJson(route('api.maintenance.destroy'), [
            'monitoring_id' => $monitoring->id,
        ])->assertOk()->assertJsonStructure(['message']);
    }

    public function test_user_can_schedule_maintenance_for_single_monitoring(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse = $this->actingAs($this->user)->post(route('maintenance.store'), [
            'scope' => 'monitoring',
            'monitoring_id' => $monitoring->id,
            'maintenance_from' => '2026-07-01T10:00',
            'maintenance_until' => '2026-07-01T11:00',
        ]);

        $testResponse->assertRedirect(route('maintenance.index'));
        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'maintenance_from' => '2026-07-01 10:00:00',
            'maintenance_until' => '2026-07-01 11:00:00',
        ]);
    }

    public function test_user_can_schedule_maintenance_for_monitoring_group(): void
    {
        $group = MonitoringGroup::factory()->for($this->user)->create();
        $firstMonitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $secondMonitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $outsideMonitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $firstMonitoring->groups()->attach($group);
        $secondMonitoring->groups()->attach($group);

        $testResponse = $this->actingAs($this->user)->post(route('maintenance.store'), [
            'scope' => 'group',
            'monitoring_group_id' => $group->id,
            'maintenance_from' => '2026-07-02T12:00',
            'maintenance_until' => '',
        ]);

        $testResponse->assertRedirect(route('maintenance.index'));
        foreach ([$firstMonitoring, $secondMonitoring] as $monitoring) {
            $this->assertDatabaseHas('monitorings', [
                'id' => $monitoring->id,
                'maintenance_from' => '2026-07-02 12:00:00',
                'maintenance_until' => null,
            ]);
        }
        $this->assertDatabaseHas('monitorings', [
            'id' => $outsideMonitoring->id,
            'maintenance_from' => null,
            'maintenance_until' => null,
        ]);
    }

    public function test_team_admin_can_schedule_and_clear_maintenance_for_team_monitoring(): void
    {
        $team = Team::factory()->create(['created_by_user_id' => $this->user->id]);
        $team->memberships()->create([
            'user_id' => $this->user->id,
            'role' => TeamRole::ADMIN,
        ]);
        $monitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $this->user->id,
            'name' => 'Team API',
            'preferred_location' => $this->serverInstance->code,
        ]);

        $this->actingAs($this->user)->get(route('maintenance.index'))->assertOk();
        $this->actingAs($this->user)->getJson(route('api.maintenance.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Team API']);

        $this->actingAs($this->user)->post(route('maintenance.store'), [
            'scope' => 'monitoring',
            'monitoring_id' => $monitoring->id,
            'maintenance_from' => '2026-07-03T10:00',
            'maintenance_until' => '2026-07-03T11:00',
        ])->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'maintenance_from' => '2026-07-03 10:00:00',
            'maintenance_until' => '2026-07-03 11:00:00',
        ]);

        $this->actingAs($this->user)->delete(route('maintenance.destroy'), [
            'monitoring_id' => $monitoring->id,
        ])->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'maintenance_from' => null,
            'maintenance_until' => null,
        ]);
    }

    public function test_team_member_cannot_schedule_or_clear_team_monitoring_maintenance(): void
    {
        $member = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $team = Team::factory()->create(['created_by_user_id' => $this->user->id]);
        $team->memberships()->create([
            'user_id' => $this->user->id,
            'role' => TeamRole::ADMIN,
        ]);
        $team->memberships()->create([
            'user_id' => $member->id,
            'role' => TeamRole::MEMBER,
        ]);
        $monitoring = Monitoring::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $this->user->id,
            'name' => 'Shared API',
            'preferred_location' => $this->serverInstance->code,
            'maintenance_from' => '2026-07-01 10:00:00',
            'maintenance_until' => '2026-07-01 11:00:00',
        ]);

        $this->actingAs($member)->get(route('maintenance.index'))->assertOk();
        $this->actingAs($member)->getJson(route('api.maintenance.index'))
            ->assertOk()
            ->assertJsonPath('data.windows.data.0.name', 'Shared API')
            ->assertJsonPath('data.windows.data.0.can_manage', false);

        $this->actingAs($member)->post(route('maintenance.store'), [
            'scope' => 'monitoring',
            'monitoring_id' => $monitoring->id,
            'maintenance_from' => '2026-07-03T10:00',
            'maintenance_until' => '2026-07-03T11:00',
        ])->assertSessionHasErrors(['monitoring_id']);

        $this->actingAs($member)->delete(route('maintenance.destroy'), [
            'monitoring_id' => $monitoring->id,
        ])->assertSessionHasErrors(['monitoring_id']);

        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'maintenance_from' => '2026-07-01 10:00:00',
            'maintenance_until' => '2026-07-01 11:00:00',
        ]);
    }

    public function test_user_can_clear_monitoring_maintenance_window(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
            'maintenance_from' => '2026-07-01 10:00:00',
            'maintenance_until' => '2026-07-01 11:00:00',
        ]);

        $testResponse = $this->actingAs($this->user)->delete(route('maintenance.destroy'), [
            'monitoring_id' => $monitoring->id,
        ]);

        $testResponse->assertRedirect(route('maintenance.index'));
        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'maintenance_from' => null,
            'maintenance_until' => null,
        ]);
    }

    public function test_user_cannot_schedule_maintenance_for_foreign_monitoring_or_group(): void
    {
        $otherUser = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $foreignMonitoring = Monitoring::factory()->for($otherUser)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $foreignGroup = MonitoringGroup::factory()->for($otherUser)->create();

        $this->actingAs($this->user)->post(route('maintenance.store'), [
            'scope' => 'monitoring',
            'monitoring_id' => $foreignMonitoring->id,
            'maintenance_from' => '2026-07-01T10:00',
        ])->assertSessionHasErrors(['monitoring_id']);

        $this->actingAs($this->user)->post(route('maintenance.store'), [
            'scope' => 'group',
            'monitoring_group_id' => $foreignGroup->id,
            'maintenance_from' => '2026-07-01T10:00',
        ])->assertSessionHasErrors(['monitoring_group_id']);
    }

    public function test_monitoring_create_and_edit_forms_do_not_render_maintenance_fields(): void
    {
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'type' => MonitoringType::PING,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.create'));
        $editResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertDontSeeHtml('name="maintenance_from"');
        $testResponse->assertDontSeeHtml('name="maintenance_until"');

        $editResponse->assertOk();
        $editResponse->assertDontSeeHtml('name="maintenance_from"');
        $editResponse->assertDontSeeHtml('name="maintenance_until"');
    }
}
