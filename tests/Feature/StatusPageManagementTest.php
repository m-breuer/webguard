<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\IncidentUpdateStatus;
use App\Enums\StatusPageComponentSource;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class StatusPageManagementTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_status_page_form_renders_available_monitorings_and_default_components(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->for($user)->create(['name' => 'Primary API']);

        $testResponse = $this->actingAs($user)->get(route('status-pages.create'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('status_page.form.components'));
        $testResponse->assertSeeHtml('API');
        $testResponse->assertSeeHtml('Web App');
        $testResponse->assertSeeHtml('Workers');
        $testResponse->assertSeeHtml('Database');
        $testResponse->assertSeeText('Primary API');
    }

    public function test_user_can_create_component_based_status_page(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $apiMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $workerMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Queue Worker']);

        $testResponse = $this->actingAs($user)->post(route('status-pages.store'), [
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'description' => 'Customer-facing status page',
            'is_public' => '1',
            'components' => [
                [
                    'name' => 'API',
                    'monitoring_ids' => [$apiMonitoring->id],
                ],
                [
                    'name' => 'Workers',
                    'monitoring_ids' => [$workerMonitoring->id],
                ],
            ],
        ]);

        $statusPage = StatusPage::query()->firstOrFail();

        $testResponse->assertRedirect(route('status-pages.show', $statusPage));
        $this->assertDatabaseHas('status_pages', [
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'API',
            'position' => 0,
            'source_type' => 'manual',
            'monitoring_group_id' => null,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'Workers',
            'position' => 1,
            'source_type' => 'manual',
            'monitoring_group_id' => null,
        ]);
        $this->assertDatabaseHas('status_page_component_monitoring', [
            'monitoring_id' => $apiMonitoring->id,
            'position' => 0,
        ]);
    }

    public function test_user_can_list_show_edit_update_and_delete_status_page(): void
    {
        Date::setTestNow('2026-06-27 10:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $group = MonitoringGroup::factory()->for($user)->create(['name' => 'Core Group']);
        $group->monitorings()->attach($monitoring->id);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Old Status',
            'slug' => 'old-status',
            'description' => 'Old description',
            'is_public' => true,
        ]);
        $component = $statusPage->components()->create([
            'name' => 'Core',
            'position' => 0,
            'source_type' => StatusPageComponentSource::MONITORING_GROUP,
            'monitoring_group_id' => $group->id,
        ]);
        $component->monitorings()->attach($monitoring->id, ['position' => 0]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDay(),
            'up_at' => Date::now()->subHours(23),
        ]);

        $this->actingAs($user)->get(route('status-pages.index'))
            ->assertOk()
            ->assertSeeText('Old Status');

        $this->actingAs($user)->get(route('status-pages.show', $statusPage))
            ->assertOk()
            ->assertSeeText('Old Status');

        $this->actingAs($user)->get(route('status-pages.edit', $statusPage))
            ->assertOk()
            ->assertSeeText('Old Status')
            ->assertSeeText('Primary API');

        $this->actingAs($user)->put(route('status-pages.update', $statusPage), [
            'name' => 'Updated Status',
            'slug' => 'updated-status',
            'description' => 'Updated description',
            'is_public' => '0',
            'components' => [
                [
                    'name' => 'Updated API',
                    'source_type' => StatusPageComponentSource::MANUAL->value,
                    'monitoring_ids' => [$monitoring->id],
                ],
                [
                    'name' => 'Updated Group',
                    'source_type' => StatusPageComponentSource::MONITORING_GROUP->value,
                    'monitoring_group_id' => $group->id,
                ],
            ],
        ])->assertRedirect(route('status-pages.show', $statusPage))
            ->assertSessionHas('success', __('status_page.messages.updated'));

        $this->assertDatabaseHas('status_pages', [
            'id' => $statusPage->id,
            'name' => 'Updated Status',
            'slug' => 'updated-status',
            'is_public' => false,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'Updated Group',
            'source_type' => StatusPageComponentSource::MONITORING_GROUP->value,
            'monitoring_group_id' => $group->id,
        ]);

        $this->actingAs($user)->delete(route('status-pages.destroy', $statusPage))
            ->assertRedirect(route('status-pages.index'))
            ->assertSessionHas('success', __('status_page.messages.deleted'));

        $this->assertDatabaseMissing('status_pages', ['id' => $statusPage->id]);
    }

    public function test_demo_user_cannot_open_mutating_status_page_screens(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $demoUser = User::factory()->create([
            'package_id' => $package->id,
            'role' => \App\Enums\UserRole::DEMO,
        ]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $demoUser->id,
            'name' => 'Demo Status',
            'slug' => 'demo-status',
            'is_public' => true,
        ]);

        $this->actingAs($demoUser)->get(route('status-pages.create'))->assertForbidden();
        $this->actingAs($demoUser)->get(route('status-pages.edit', $statusPage))->assertForbidden();
        $this->actingAs($demoUser)->delete(route('status-pages.destroy', $statusPage))->assertForbidden();
    }

    public function test_status_page_group_components_cannot_reference_another_users_groups(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $foreignGroup = MonitoringGroup::factory()->for($otherUser)->create();

        $testResponse = $this->from(route('status-pages.create'))
            ->actingAs($user)
            ->post(route('status-pages.store'), [
                'name' => 'Acme Status',
                'slug' => 'acme-status',
                'is_public' => '1',
                'components' => [
                    [
                        'name' => 'Foreign Group',
                        'source_type' => 'monitoring_group',
                        'monitoring_group_id' => $foreignGroup->id,
                    ],
                ],
            ]);

        $testResponse->assertRedirect(route('status-pages.create'));
        $testResponse->assertSessionHasErrors(['components.0.monitoring_group_id']);
        $this->assertDatabaseMissing('status_pages', ['slug' => 'acme-status']);
    }

    public function test_status_page_components_cannot_reference_another_users_monitorings(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $otherMonitoring = Monitoring::factory()->for($otherUser)->create();

        $testResponse = $this->from(route('status-pages.create'))
            ->actingAs($user)
            ->post(route('status-pages.store'), [
                'name' => 'Acme Status',
                'slug' => 'acme-status',
                'is_public' => '1',
                'components' => [
                    [
                        'name' => 'API',
                        'monitoring_ids' => [$otherMonitoring->id],
                    ],
                ],
            ]);

        $testResponse->assertRedirect(route('status-pages.create'));
        $testResponse->assertSessionHasErrors(['components.0.monitoring_ids.0']);
        $this->assertDatabaseMissing('status_pages', ['slug' => 'acme-status']);
    }

    public function test_user_cannot_view_another_users_status_page_management_screen(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $owner = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Status',
            'slug' => 'owner-status',
            'is_public' => true,
        ]);

        $testResponse = $this->actingAs($otherUser)->get(route('status-pages.show', $statusPage));

        $testResponse->assertNotFound();
    }

    public function test_user_can_add_manual_incident_update_to_status_page_incident(): void
    {
        Date::setTestNow('2026-05-14 14:30:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($monitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subMinutes(20),
            'up_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->post(
            route('status-pages.incident-updates.store', [$statusPage, $incident]),
            [
                'status' => IncidentUpdateStatus::IDENTIFIED->value,
                'message' => 'We found a saturated database connection pool and are applying a fix.',
            ]
        );

        $testResponse->assertRedirect(route('status-pages.show', $statusPage));
        $this->assertDatabaseHas('incident_updates', [
            'incident_id' => $incident->id,
            'status' => IncidentUpdateStatus::IDENTIFIED->value,
            'message' => 'We found a saturated database connection pool and are applying a fix.',
        ]);

        $publicResponse = $this->get(route('public-status-pages.show', $statusPage->slug));

        $publicResponse->assertOk();
        $publicResponse->assertSeeText(__('status_page.incident_updates.statuses.identified'));
        $publicResponse->assertSeeText('We found a saturated database connection pool and are applying a fix.');
    }

    public function test_user_cannot_add_incident_update_for_incident_outside_status_page(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $includedMonitoring = Monitoring::factory()->for($user)->create();
        $outsideMonitoring = Monitoring::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($includedMonitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $outsideMonitoring->id,
            'down_at' => now()->subMinutes(20),
            'up_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->post(
            route('status-pages.incident-updates.store', [$statusPage, $incident]),
            [
                'status' => IncidentUpdateStatus::INVESTIGATING->value,
                'message' => 'This should not be published here.',
            ]
        );

        $testResponse->assertNotFound();
        $this->assertDatabaseMissing('incident_updates', [
            'incident_id' => $incident->id,
            'message' => 'This should not be published here.',
        ]);
    }
}
