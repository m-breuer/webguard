<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class AuthenticatedNavigationTest extends TestCase
{
    public function test_member_navigation_is_grouped_without_admin_links(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('app.navigation.monitoring'));
        $testResponse->assertSeeText(__('app.navigation.workspace'));
        $testResponse->assertSeeText(__('app.navigation.sections.operations'));
        $testResponse->assertSeeText(__('app.navigation.sections.collaboration'));
        $testResponse->assertSeeText(__('monitoring.title'));
        $testResponse->assertSeeText(__('maintenance.title'));
        $testResponse->assertSeeText(__('monitoring_group.title'));
        $testResponse->assertSeeText(__('team.title'));
        $testResponse->assertSeeText(__('status_page.title'));
        $testResponse->assertDontSeeText(__('app.navigation.sections.administration'));
        $testResponse->assertDontSeeText(__('admin.dashboard.users.heading'));
    }

    public function test_admin_navigation_exposes_administration_group(): void
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
}
