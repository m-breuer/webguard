<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Tests\TestCase;

class StatusPageManagementTest extends TestCase
{
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
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'Workers',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('status_page_component_monitoring', [
            'monitoring_id' => $apiMonitoring->id,
            'position' => 0,
        ]);
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
}
