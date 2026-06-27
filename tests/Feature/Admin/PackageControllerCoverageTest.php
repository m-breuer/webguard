<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class PackageControllerCoverageTest extends TestCase
{
    public function test_admin_can_filter_packages_and_receive_async_rows(): void
    {
        Package::factory()->create(['monitoring_limit' => 5, 'price' => 9.99, 'is_selectable' => true]);
        Package::factory()->create(['monitoring_limit' => 50, 'price' => 49.99, 'is_selectable' => false]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->getJson(route('admin.packages.index', [
                'search' => '49',
                'is_selectable' => '0',
                'sort' => 'price',
                'direction' => 'desc',
            ]))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertSee('49.99');
    }

    public function test_admin_can_create_edit_update_and_delete_unused_package(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)->get(route('admin.packages.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.packages.store'), [
            'monitoring_limit' => 12,
            'price' => 14.99,
            'is_selectable' => '1',
        ])->assertRedirect(route('admin.packages.index'))
            ->assertSessionHas('success', __('admin.packages.messages.package_created'));

        $package = Package::query()->withoutGlobalScope('selectable')->where('monitoring_limit', 12)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.packages.edit', $package))->assertOk();

        $this->actingAs($admin)->put(route('admin.packages.update', $package), [
            'monitoring_limit' => 15,
            'price' => 19.99,
            'is_selectable' => '0',
        ])->assertRedirect(route('admin.packages.index'))
            ->assertSessionHas('success', __('admin.packages.messages.package_updated'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'monitoring_limit' => 15,
            'is_selectable' => false,
        ]);

        $this->actingAs($admin)->delete(route('admin.packages.destroy', $package))
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHas('success', __('admin.packages.messages.package_deleted'));

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_admin_cannot_delete_package_assigned_to_users(): void
    {
        $package = Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        User::factory()->create(['package_id' => $package->id]);

        $this->actingAs($admin)->delete(route('admin.packages.destroy', $package))
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHas('error', __('admin.packages.messages.package_in_use'));

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }
}
