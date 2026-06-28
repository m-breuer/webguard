<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class PackageControllerTest extends TestCase
{
    public function test_admin_can_open_package_create_form(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.packages.create'))
            ->assertOk()
            ->assertSeeText(__('admin.packages.create.title'))
            ->assertSeeHtml('action="' . route('admin.packages.store') . '"')
            ->assertSeeHtml('name="monitoring_limit"')
            ->assertSeeHtml('name="price"')
            ->assertSeeHtml('name="is_selectable"');
    }

    public function test_admin_can_create_package(): void
    {
        $admin = $this->adminUser();

        $testResponse = $this->actingAs($admin)
            ->post(route('admin.packages.store'), [
                'monitoring_limit' => 25,
                'price' => '19.99',
                'is_selectable' => '1',
            ]);

        $testResponse->assertRedirect(route('admin.packages.index'));
        $testResponse->assertSessionHas('success', __('admin.packages.messages.package_created'));

        $this->assertDatabaseHas('packages', [
            'monitoring_limit' => 25,
            'price' => 19.99,
            'is_selectable' => true,
        ]);
    }

    public function test_admin_can_edit_package(): void
    {
        $admin = $this->adminUser();
        $package = Package::factory()->create([
            'monitoring_limit' => 12,
            'price' => 7.50,
            'is_selectable' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.edit', $package))
            ->assertOk()
            ->assertSeeText(__('admin.packages.edit.title'))
            ->assertSeeHtml('action="' . route('admin.packages.update', $package) . '"')
            ->assertSeeHtml('value="12"')
            ->assertSeeHtml('value="7.5"');
    }

    public function test_admin_can_update_package(): void
    {
        $admin = $this->adminUser();
        $package = Package::factory()->create([
            'monitoring_limit' => 5,
            'price' => 4.99,
            'is_selectable' => true,
        ]);

        $testResponse = $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                'monitoring_limit' => 50,
                'price' => '29.99',
                'is_selectable' => '0',
            ]);

        $testResponse->assertRedirect(route('admin.packages.index'));
        $testResponse->assertSessionHas('success', __('admin.packages.messages.package_updated'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'monitoring_limit' => 50,
            'price' => 29.99,
            'is_selectable' => false,
        ]);
    }

    public function test_admin_can_delete_unused_package(): void
    {
        $admin = $this->adminUser();
        $package = Package::factory()->create();

        $testResponse = $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package));

        $testResponse->assertRedirect(route('admin.packages.index'));
        $testResponse->assertSessionHas('success', __('admin.packages.messages.package_deleted'));
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_admin_cannot_delete_package_assigned_to_users(): void
    {
        $admin = $this->adminUser();
        $package = Package::factory()->create();
        User::factory()->create(['package_id' => $package->id]);

        $testResponse = $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package));

        $testResponse->assertRedirect(route('admin.packages.index'));
        $testResponse->assertSessionHas('error', __('admin.packages.messages.package_in_use'));
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    private function adminUser(): User
    {
        Package::factory()->create();

        return User::factory()->create(['role' => UserRole::ADMIN]);
    }
}
