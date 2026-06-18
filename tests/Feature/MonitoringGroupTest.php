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
            'public_label_enabled' => '1',
        ]);

        $testResponse->assertRedirect(route('monitoring-groups.index'));
        $this->assertDatabaseHas('monitoring_groups', [
            'user_id' => $this->user->id,
            'name' => 'Production',
            'description' => 'Critical production endpoints',
            'public_label_enabled' => true,
        ]);

        $monitoringGroup = MonitoringGroup::query()->where('name', 'Production')->firstOrFail();

        $updateResponse = $this->actingAs($this->user)->patch(route('monitoring-groups.update', $monitoringGroup), [
            'name' => 'Production APIs',
            'description' => '',
            'public_label_enabled' => '0',
        ]);

        $updateResponse->assertRedirect(route('monitoring-groups.index'));
        $this->assertDatabaseHas('monitoring_groups', [
            'id' => $monitoringGroup->id,
            'name' => 'Production APIs',
            'description' => null,
            'public_label_enabled' => false,
        ]);
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

        $createResponse = $this->actingAs($this->user)->get(route('monitorings.create'));
        $editResponse = $this->actingAs($this->user)->get(route('monitorings.edit', $monitoring));

        $createResponse->assertOk();
        $createResponse->assertSeeText('No group');
        $createResponse->assertSeeHtml('<option value="" selected>No group</option>');

        $editResponse->assertOk();
        $editResponse->assertSeeText('No group');
        $editResponse->assertSeeHtml('<option value="" selected>No group</option>');
    }

    public function test_selecting_no_group_detaches_existing_group_assignments(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create(['name' => 'Production']);
        $monitoring = Monitoring::factory()->for($this->user)->create([
            'preferred_location' => $this->serverInstance->code,
        ]);
        $monitoring->groups()->attach($monitoringGroup);

        $updateResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), $this->httpPayload([
            'name' => 'Standalone HTTP Monitoring',
            'group_ids' => [''],
        ]));

        $updateResponse->assertRedirect(route('monitorings.show', $monitoring));
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
        $this->actingAs($demoUser)->delete(route('monitoring-groups.destroy', $monitoringGroup))->assertForbidden();
    }

    public function test_public_group_label_only_lists_monitorings_with_public_labels_enabled(): void
    {
        $monitoringGroup = MonitoringGroup::factory()->for($this->user)->create([
            'name' => 'Public Production',
            'public_label_enabled' => true,
        ]);
        $visibleMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Visible API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://visible.example.com',
            'preferred_location' => $this->serverInstance->code,
            'public_label_enabled' => true,
        ]);
        $hiddenMonitoring = Monitoring::factory()->for($this->user)->create([
            'name' => 'Hidden API',
            'preferred_location' => $this->serverInstance->code,
            'public_label_enabled' => false,
        ]);

        $monitoringGroup->monitorings()->attach([$visibleMonitoring->id, $hiddenMonitoring->id]);

        $testResponse = $this->get(route('public-monitoring-groups.show', $monitoringGroup));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Public Production');
        $testResponse->assertSeeText('Visible API');
        $testResponse->assertDontSeeText('Hidden API');
        $testResponse->assertSeeHtml('href="' . route('public-label', $visibleMonitoring) . '"');

        $otherUser = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $authenticatedResponse = $this->actingAs($otherUser)->get(route('public-monitoring-groups.show', $monitoringGroup));

        $authenticatedResponse->assertOk();
        $authenticatedResponse->assertSeeText('Visible API');
        $authenticatedResponse->assertDontSeeText('Hidden API');

        $monitoringGroup->update(['public_label_enabled' => false]);

        $this->get(route('public-monitoring-groups.show', $monitoringGroup))->assertNotFound();
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
