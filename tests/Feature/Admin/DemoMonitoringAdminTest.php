<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class DemoMonitoringAdminTest extends TestCase
{
    public function test_admin_dashboard_links_to_demo_monitoring_management(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $testResponse = $this->actingAs($admin)->get(route('admin.dashboard'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('admin.dashboard.demo_monitorings.heading'));
        $testResponse->assertSeeHtml('href="' . route('admin.demo-monitorings.index') . '"');
    }

    public function test_admin_can_list_demo_user_monitorings_without_admin_monitorings(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $demoUser = User::factory()->create(['role' => UserRole::DEMO]);

        Monitoring::factory()->for($demoUser)->create([
            'name' => 'Demo HTTP Check',
            'type' => MonitoringType::HTTP,
            'target' => 'https://demo.example.test',
        ]);
        Monitoring::factory()->for($admin)->create([
            'name' => 'Admin Private Check',
            'type' => MonitoringType::HTTP,
            'target' => 'https://admin.example.test',
        ]);

        $testResponse = $this->actingAs($admin)->get(route('admin.demo-monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Demo HTTP Check');
        $testResponse->assertDontSeeText('Admin Private Check');
        $testResponse->assertSeeText($demoUser->email);
    }

    public function test_admin_can_create_monitoring_for_demo_user(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 5]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $demoUser = User::factory()->create([
            'role' => UserRole::DEMO,
            'package_id' => $package->id,
        ]);
        ServerInstance::query()->create([
            'code' => 'admin-demo-1',
            'ip_address' => '10.0.0.10',
            'api_key_hash' => 'secret',
            'is_active' => true,
        ]);

        $testResponse = $this->actingAs($admin)->post(route('admin.demo-monitorings.store'), [
            'name' => 'Demo Managed Check',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://demo-managed.example.test',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => '200-299',
            'preferred_location' => 'admin-demo-1',
            'notification_on_failure' => '1',
            'ssl_expiry_warning_days' => 7,
        ]);

        $testResponse->assertRedirect(route('admin.demo-monitorings.index'));
        $testResponse->assertSessionHas('success', __('admin.demo_monitorings.messages.created'));

        $this->assertDatabaseHas('monitorings', [
            'user_id' => $demoUser->id,
            'name' => 'Demo Managed Check',
            'target' => 'https://demo-managed.example.test',
            'type' => MonitoringType::HTTP->value,
        ]);
    }

    public function test_demo_user_still_cannot_create_monitorings_directly(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 5]);
        $demoUser = User::factory()->create([
            'role' => UserRole::DEMO,
            'package_id' => $package->id,
        ]);

        $this->actingAs($demoUser)
            ->get(route('monitorings.create'))
            ->assertForbidden();
    }
}
