<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\StatusPage;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Support\HttpStatusCodeRanges;
use Tests\TestCase;

class MonitoringGroupTest extends TestCase
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

    public function test_user_can_manage_monitoring_groups(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitoring-groups.store'), [
            'name' => 'Production',
            'description' => 'Critical production endpoints',
        ]);

        $testResponse->assertRedirect(route('monitoring-groups.index'));
        $this->assertDatabaseHas('monitoring_groups', [
            'user_id' => $this->user->id,
            'name' => 'Production',
            'description' => 'Critical production endpoints',
        ]);

        $monitoringGroup = MonitoringGroup::query()->where('name', 'Production')->firstOrFail();

        $updateResponse = $this->actingAs($this->user)->patch(route('monitoring-groups.update', $monitoringGroup), [
            'name' => 'Production APIs',
            'description' => '',
        ]);

        $updateResponse->assertRedirect(route('monitoring-groups.index'));
        $this->assertDatabaseHas('monitoring_groups', [
            'id' => $monitoringGroup->id,
            'name' => 'Production APIs',
            'description' => null,
        ]);
    }

    public function test_user_can_list_monitoring_groups_with_monitoring_counts(): void
    {
        $zulu = MonitoringGroup::factory()->for($this->user)->create([
            'name' => 'Zulu Services',
            'description' => 'Secondary systems',
        ]);
        $alpha = MonitoringGroup::factory()->for($this->user)->create([
            'name' => 'Alpha Services',
            'description' => 'Primary systems',
        ]);
        MonitoringGroup::factory()
            ->for(User::factory()->create(['package_id' => Package::factory()->create()->id]))
            ->create(['name' => 'Foreign Services']);

        Monitoring::factory()
            ->count(2)
            ->for($this->user)
            ->create(['preferred_location' => $this->serverInstance->code])
            ->each(fn (Monitoring $monitoring) => $monitoring->groups()->attach($alpha));
        Monitoring::factory()
            ->for($this->user)
            ->create(['preferred_location' => $this->serverInstance->code])
            ->groups()
            ->attach($zulu);

        $testResponse = $this->actingAs($this->user)->get(route('monitoring-groups.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeInOrder(['Alpha Services', 'Zulu Services']);
        $testResponse->assertSeeText('Primary systems');
        $testResponse->assertSeeText('Secondary systems');
        $testResponse->assertSeeText(trans_choice('monitoring_group.monitorings_count', 2, ['count' => 2]));
        $testResponse->assertSeeText(trans_choice('monitoring_group.monitorings_count', 1, ['count' => 1]));
        $testResponse->assertSeeHtml('action="' . route('monitoring-groups.publish-status-page', $alpha) . '"');
        $testResponse->assertDontSeeText('Foreign Services');
    }

    public function test_delete_action_uses_app_confirm_dialog(): void
    {
        MonitoringGroup::factory()->for($this->user)->create();

        $testResponse = $this->actingAs($this->user)->get(route('monitoring-groups.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-confirm-message="' . __('monitoring_group.actions.delete.confirmation') . '"');
        $testResponse->assertDontSeeHtml('if (confirm(');
        $testResponse->assertDontSeeHtml('x-on:click.prevent');
    }

    public function test_user_can_view_create_and_edit_monitoring_group_forms(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create([
            'name' => 'Production',
            'description' => 'Critical production endpoints',
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitoring-groups.create'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring_group.create.title'));
        $testResponse->assertSeeHtml('action="' . route('monitoring-groups.store') . '"');

        $editResponse = $this->actingAs($this->user)->get(route('monitoring-groups.edit', $monitoringGroup));

        $editResponse->assertOk();
        $editResponse->assertSeeText(__('monitoring_group.edit.title', ['group' => 'Production']));
        $editResponse->assertSeeHtml('action="' . route('monitoring-groups.update', $monitoringGroup) . '"');
        $editResponse->assertSeeHtml('value="Production"');
        $editResponse->assertDontSeeText(__('monitoring.form.public_label'));
    }

    public function test_user_can_assign_monitorings_when_creating_and_editing_a_group(): void
    {
        $api = Monitoring::factory()->for($this->user)->create([
            'name' => 'Checkout API',
            'target' => 'https://api.example.test',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $website = Monitoring::factory()->for($this->user)->create([
            'name' => 'Website',
            'target' => 'https://example.test',
            'preferred_location' => $this->serverInstance->code,
        ]);

        $createForm = $this->actingAs($this->user)->get(route('monitoring-groups.create'));

        $createForm->assertOk();
        $createForm->assertSeeText('Checkout API — https://api.example.test');
        $createForm->assertSeeHtml('name="monitoring_ids[]"');

        $this->actingAs($this->user)->post(route('monitoring-groups.store'), [
            'name' => 'Production',
            'monitoring_ids' => [$api->id, $website->id],
        ])->assertRedirect(route('monitoring-groups.index'));

        $monitoringGroup = MonitoringGroup::query()->where('name', 'Production')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$api->id, $website->id],
            $monitoringGroup->monitorings()->pluck('monitorings.id')->all()
        );

        $editForm = $this->actingAs($this->user)->get(route('monitoring-groups.edit', $monitoringGroup));
        $editForm->assertOk();
        $editForm->assertSeeText('Checkout API — https://api.example.test');
        $editForm->assertSeeText('Website — https://example.test');

        $this->actingAs($this->user)->patch(route('monitoring-groups.update', $monitoringGroup), [
            'name' => 'Production',
            'monitoring_ids' => [$website->id],
        ])->assertRedirect(route('monitoring-groups.index'));

        $this->assertSame(
            [$website->id],
            $monitoringGroup->monitorings()->pluck('monitorings.id')->all()
        );
    }

    public function test_group_assignment_only_accepts_monitorings_the_user_can_manage(): void
    {
        $otherUser = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $foreignMonitoring = Monitoring::factory()->for($otherUser)->create([
            'name' => 'Foreign',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $team = Team::factory()->create(['created_by_user_id' => $otherUser->id]);
        TeamMembership::factory()->for($team)->for($this->user)->create();
        $teamMonitoring = Monitoring::factory()->for($team)->create([
            'user_id' => null,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $formResponse = $this->actingAs($this->user)->get(route('monitoring-groups.create'));
        $formResponse->assertDontSeeText('Foreign');
        $formResponse->assertDontSeeText($teamMonitoring->name);

        $response = $this->from(route('monitoring-groups.create'))
            ->actingAs($this->user)
            ->post(route('monitoring-groups.store'), [
                'name' => 'Restricted',
                'monitoring_ids' => [$foreignMonitoring->id, $teamMonitoring->id],
            ]);

        $response->assertRedirect(route('monitoring-groups.create'));
        $response->assertSessionHasErrors(['monitoring_ids.0', 'monitoring_ids.1']);
        $response->assertSessionHasInput('monitoring_ids', [$foreignMonitoring->id, $teamMonitoring->id]);
        $this->assertDatabaseMissing('monitoring_groups', ['name' => 'Restricted']);
    }

    public function test_group_assignment_keeps_team_monitorings_outside_personal_groups(): void
    {
        $team = Team::factory()->create(['created_by_user_id' => $this->user->id]);
        TeamMembership::factory()->for($team)->for($this->user)->admin()->create();
        $teamMonitoring = Monitoring::factory()->for($team)->create([
            'user_id' => null,
            'name' => 'Managed Team API',
            'preferred_location' => $this->serverInstance->code,
        ]);

        $response = $this->from(route('monitoring-groups.create'))
            ->actingAs($this->user)
            ->post(route('monitoring-groups.store'), [
                'name' => 'Team Services',
                'monitoring_ids' => [$teamMonitoring->id],
            ]);

        $response->assertRedirect(route('monitoring-groups.create'));
        $response->assertSessionHasErrors(['monitoring_ids.0']);
        $this->assertDatabaseMissing('monitoring_groups', ['name' => 'Team Services']);
    }

    public function test_updating_group_preserves_assignments_that_are_no_longer_manageable(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $teamOwner = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $team = Team::factory()->create(['created_by_user_id' => $teamOwner->id]);
        TeamMembership::factory()->for($team)->for($this->user)->create();
        $teamMonitoring = Monitoring::factory()->for($team)->create([
            'user_id' => null,
            'preferred_location' => $this->serverInstance->code,
        ]);
        $monitoringGroup->monitorings()->attach($teamMonitoring);

        $this->actingAs($this->user)->patch(route('monitoring-groups.update', $monitoringGroup), [
            'name' => 'Production',
            'monitoring_ids' => [],
        ])->assertRedirect(route('monitoring-groups.index'));

        $this->assertDatabaseHas('monitoring_group_monitoring', [
            'monitoring_group_id' => $monitoringGroup->id,
            'monitoring_id' => $teamMonitoring->id,
        ]);
    }

    public function test_user_cannot_edit_foreign_monitoring_group(): void
    {
        $otherUser = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $foreignGroup = MonitoringGroup::factory()->for($otherUser)->create();

        $this->actingAs($this->user)
            ->get(route('monitoring-groups.edit', $foreignGroup))
            ->assertNotFound();
    }

    public function test_group_name_is_unique_per_user(): void
    {
        MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $otherUser = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        MonitoringGroup::factory()->for($otherUser)->create(['name' => 'Production']);

        $testResponse = $this->from(route('monitoring-groups.create'))
            ->actingAs($this->user)
            ->post(route('monitoring-groups.store'), [
                'name' => 'Production',
            ]);

        $testResponse->assertRedirect(route('monitoring-groups.create'));
        $testResponse->assertSessionHasErrors(['name']);
    }

    public function test_monitoring_can_be_assigned_to_multiple_groups_and_filtered_by_group(): void
    {
        $production = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $billing = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Billing']);
        $operations = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Operations']);

        $createResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $this->httpPayload([
            'name' => 'Checkout API',
            'group_ids' => [$production->id, $billing->id],
        ]));

        $createResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Checkout API')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$production->id, $billing->id],
            $monitoring->groups()->pluck('monitoring_groups.id')->all()
        );

        $otherMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Worker Queue',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $otherMonitoring->groups()->attach($operations);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.index', ['group_id' => $production->id]));

        $testResponse->assertOk();
        $testResponse->assertSee('Checkout API');
        $testResponse->assertDontSee('Worker Queue');
        $testResponse->assertSeeHtml('value="' . $production->id . '" selected');
    }

    public function test_monitorings_and_groups_can_exist_without_assignments(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Unassigned Group']);

        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), $this->httpPayload([
            'name' => 'Standalone HTTP Monitoring',
        ]));

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Standalone HTTP Monitoring')->firstOrFail();

        $this->assertSame(0, $monitoring->groups()->count());
        $this->assertSame(0, $monitoringGroup->monitorings()->count());
        $this->assertDatabaseMissing('monitoring_group_monitoring', [
            'monitoring_id' => $monitoring->id,
        ]);
    }

    public function test_create_and_edit_forms_offer_explicit_no_group_selection(): void
    {
        MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.create'));
        $editResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText('No group');
        $testResponse->assertSeeText('Production');
        $testResponse->assertSeeHtml('name="group_ids[]"');
        $testResponse->assertSeeText('Select all');

        $editResponse->assertOk();
        $editResponse->assertSeeText('No group');
        $editResponse->assertSeeText('Production');
        $editResponse->assertSeeHtml('name="group_ids[]"');
        $editResponse->assertSeeText('Select all');
    }

    public function test_selecting_no_group_detaches_existing_group_assignments(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $monitoring->groups()->attach($monitoringGroup);

        $testResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), $this->httpPayload([
            'name' => 'Standalone HTTP Monitoring',
            'group_ids' => [''],
        ]));

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));
        $this->assertSame(0, $monitoring->refresh()->groups()->count());
    }

    public function test_foreign_groups_are_not_visible_or_assignable(): void
    {
        $ownGroup = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Own Group']);
        $otherUser = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $foreignGroup = MonitoringGroup::factory()->for($otherUser)->create(['name' => 'Foreign Group']);

        $testResponse = $this->actingAs($this->user)->get(route('monitorings.create'));
        $testResponse->assertOk();
        $testResponse->assertSeeText('Own Group');
        $testResponse->assertDontSeeText('Foreign Group');

        $storeResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), $this->httpPayload([
                'group_ids' => [$ownGroup->id, $foreignGroup->id],
            ]));

        $storeResponse->assertRedirect(route('monitorings.create'));
        $storeResponse->assertSessionHasErrors(['group_ids.1']);
    }

    public function test_deleting_group_detaches_monitorings_without_deleting_them(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create();
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $monitoring->groups()->attach($monitoringGroup);

        $testResponse = $this->actingAs($this->user)
            ->delete(route('monitoring-groups.destroy', $monitoringGroup));

        $testResponse->assertRedirect(route('monitoring-groups.index'));
        $this->assertDatabaseMissing('monitoring_groups', ['id' => $monitoringGroup->id]);
        $this->assertDatabaseHas('monitorings', ['id' => $monitoring->id]);
        $this->assertDatabaseMissing('monitoring_group_monitoring', [
            'monitoring_group_id' => $monitoringGroup->id,
            'monitoring_id' => $monitoring->id,
        ]);
    }

    public function test_demo_user_cannot_mutate_monitoring_groups(): void
    {
        $demoUser = User::factory()->create([
            'package_id' => Package::factory()->create()->id,
            'role' => UserRole::DEMO->value,
        ]);
        $monitoringGroup = MonitoringGroup::factory()->for($demoUser)->create();

        $this->actingAs($demoUser)->get(route('monitoring-groups.create'))->assertForbidden();
        $this->actingAs($demoUser)->post(route('monitoring-groups.store'), ['name' => 'Demo'])->assertForbidden();
        $this->actingAs($demoUser)->get(route('monitoring-groups.edit', $monitoringGroup))->assertForbidden();
        $this->actingAs($demoUser)->post(route('monitoring-groups.publish-status-page', $monitoringGroup))->assertForbidden();
        $this->actingAs($demoUser)->delete(route('monitoring-groups.destroy', $monitoringGroup))->assertForbidden();
    }

    public function test_user_can_publish_group_as_dynamic_status_page(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create([
            'name' => 'Public Production',
            'description' => 'Customer facing services',
        ]);
        $apiMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Checkout API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://visible.example.com',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $workerMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Worker Queue',
            'preferred_location' => $this->serverInstance->code,
        ]);

        $monitoringGroup->monitorings()->attach([$apiMonitoring->id, $workerMonitoring->id]);

        $testResponse = $this->actingAs($this->user)
            ->post(route('monitoring-groups.publish-status-page', $monitoringGroup));

        $statusPage = StatusPage::query()->where('name', 'Public Production')->firstOrFail();

        $testResponse->assertRedirect(route('status-pages.show', $statusPage));
        $this->assertDatabaseHas('status_pages', [
            'id' => $statusPage->id,
            'user_id' => $this->user->id,
            'slug' => null,
            'is_public' => true,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'monitoring_group_id' => $monitoringGroup->id,
            'source_type' => 'monitoring_group',
            'name' => 'Public Production',
        ]);

        $publicResponse = $this->get(route('public-status-pages.show', $statusPage));

        $publicResponse->assertOk();
        $publicResponse->assertSeeText('Public Production');
        $publicResponse->assertSeeText('Checkout API');
        $publicResponse->assertSeeText('Worker Queue');
        $this->get('/label/groups/' . $monitoringGroup->id)->assertNotFound();
    }

    public function test_group_backed_status_page_component_follows_group_membership(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $initialMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Initial API',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $laterMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Added API',
            'preferred_location' => $this->serverInstance->code,
        ]);
        $monitoringGroup->monitorings()->attach($initialMonitoring->id);

        $this->actingAs($this->user)->post(route('monitoring-groups.publish-status-page', $monitoringGroup));
        $statusPage = StatusPage::query()->where('name', 'Production')->firstOrFail();

        $monitoringGroup->monitorings()->attach($laterMonitoring->id);

        $testResponse = $this->get(route('public-status-pages.show', $statusPage));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Initial API');
        $testResponse->assertSeeText('Added API');
    }

    private function httpPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'HTTP Monitoring',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => HttpStatusCodeRanges::DEFAULT,
            'http_headers' => null,
            'preferred_location' => $this->serverInstance->code,
        ], $overrides);
    }
}
