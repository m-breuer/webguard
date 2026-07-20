<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class AuthenticatedNavigationTest extends TestCase
{
    public function test_member_navigation_exposes_one_dashboard_and_grouped_operations_destinations(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('app.navigation.dashboard'));
        $testResponse->assertSeeText(__('app.navigation.monitorings'));
        $testResponse->assertSeeText(__('app.navigation.monitoring_groups'));
        $testResponse->assertSeeText(__('app.navigation.status_pages'));
        $testResponse->assertSeeText(__('app.navigation.incidents'));
        $testResponse->assertSeeText(__('app.navigation.maintenance'));
        $testResponse->assertSeeText(__('app.navigation.teams'));
        $testResponse->assertSeeHtml('href="' . route('dashboard') . '"');
        $testResponse->assertSeeHtml('href="' . route('monitorings.index') . '"');
        $testResponse->assertSeeHtml('data-secondary-navigation');
        $testResponse->assertSeeHtml('data-sidebar-toggle');
        $testResponse->assertSeeHtml(__('app.navigation.collapse_sidebar'));
        $this->assertSame(1, mb_substr_count($testResponse->getContent() ?? '', 'data-primary-destination'));
        $testResponse->assertDontSeeText(__('app.navigation.signal_room'));
        $testResponse->assertDontSeeText(__('app.navigation.workspace'));
        $testResponse->assertDontSeeHtml('data-workspace-navigation');
        $testResponse->assertDontSeeText(__('app.navigation.sections.administration'));
        $testResponse->assertDontSeeText(__('admin.dashboard.users.heading'));
    }

    public function test_admin_navigation_exposes_administration_links_from_the_account_menu(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $admin = User::factory()->create([
            'package_id' => $package->id,
            'role' => UserRole::ADMIN->value,
        ]);

        $testResponse = $this->actingAs($admin)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('app.navigation.sections.administration'));
        $testResponse->assertSeeText(__('admin.dashboard.heading'));
        $testResponse->assertSeeText(__('admin.dashboard.users.heading'));
        $testResponse->assertSeeText(__('admin.dashboard.packages.heading'));
        $testResponse->assertSeeText(__('admin.dashboard.instances.heading'));
        $testResponse->assertSeeText(__('admin.dashboard.apis.heading'));
        $testResponse->assertDontSee('/admin/demo-monitorings');
        $testResponse->assertSeeText(__('admin.dashboard.activity_logs.heading'));
    }

    public function test_desktop_navigation_uses_the_dashboard_rail(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertSeeHtml('lg:fixed');
        $testResponse->assertSeeHtml('bg-purple-950');
        $testResponse->assertSeeHtml(__('app.navigation.dashboard'));
        $testResponse->assertSeeHtml('data-secondary-navigation');
    }
}
