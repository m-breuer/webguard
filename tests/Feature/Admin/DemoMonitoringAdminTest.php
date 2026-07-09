<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\MonitoringType;
use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DemoMonitoringAdminTest extends TestCase
{
    public function test_admin_demo_monitoring_management_exposes_read_only_route(): void
    {
        $this->assertTrue(Route::has('admin.demo-monitorings.index'));
        $this->assertFalse(Route::has('admin.demo-monitorings.create'));
        $this->assertFalse(Route::has('admin.demo-monitorings.store'));
        $this->assertFalse(Route::has('admin.demo-monitorings.edit'));
        $this->assertFalse(Route::has('admin.demo-monitorings.update'));
        $this->assertFalse(Route::has('admin.demo-monitorings.destroy'));
        $this->assertFalse(Route::has('admin.demo-monitorings.show'));
    }

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
        $testResponse->assertDontSeeText(__('button.edit'));
        $testResponse->assertDontSeeText(__('button.delete'));
        $testResponse->assertDontSeeHtml('data-confirm-message');
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
