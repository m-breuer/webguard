<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DemoMonitoringAdminTest extends TestCase
{
    public function test_admin_demo_monitoring_management_route_is_not_available(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->assertFalse(Route::has('admin.demo-monitorings.index'));
        $this->actingAs($admin)
            ->get('/admin/demo-monitorings')
            ->assertNotFound();
    }

    public function test_admin_dashboard_does_not_link_to_demo_monitoring_management(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('/admin/demo-monitorings');
    }
}
