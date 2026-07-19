<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class AdminFormModalTest extends TestCase
{
    public function test_admin_resource_indexes_expose_modal_entry_points(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $package = Package::factory()->create(['is_selectable' => false]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'modal-instance',
            'ip_address' => '10.0.0.10',
            'api_key_hash' => 'secret',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeHtml('data-form-modal-name="admin-user-form-modal"')
            ->assertSeeHtml('href="' . route('admin.users.edit', $user) . '"');

        $this->actingAs($admin)
            ->get(route('admin.packages.index'))
            ->assertOk()
            ->assertSeeHtml('data-form-modal-name="admin-package-form-modal"')
            ->assertSeeHtml('href="' . route('admin.packages.edit', $package) . '"');

        $this->actingAs($admin)
            ->get(route('admin.server-instances.index'))
            ->assertOk()
            ->assertSeeHtml('data-form-modal-name="admin-server-instance-form-modal"')
            ->assertSeeHtml('href="' . route('admin.server-instances.edit', $serverInstance) . '"');
    }

    public function test_admin_can_load_create_and_edit_forms_as_modal_fragments(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->unverified()->create();
        $package = Package::factory()->create(['is_selectable' => false]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'modal-fragment-instance',
            'ip_address' => '10.0.0.11',
            'api_key_hash' => 'secret',
            'is_active' => true,
        ]);

        $modalRoutes = [
            [route('admin.users.create', ['modal' => 1]), 'admin-user-create'],
            [route('admin.users.edit', ['user' => $user, 'modal' => 1]), 'admin-user-edit'],
            [route('admin.packages.create', ['modal' => 1]), 'admin-package-create'],
            [route('admin.packages.edit', ['package' => $package, 'modal' => 1]), 'admin-package-edit'],
            [route('admin.server-instances.create', ['modal' => 1]), 'admin-server-instance-create'],
            [route('admin.server-instances.edit', ['server_instance' => $serverInstance, 'modal' => 1]), 'admin-server-instance-edit'],
        ];

        foreach ($modalRoutes as [$route, $modalForm]) {
            $this->actingAs($admin)
                ->get($route)
                ->assertOk()
                ->assertDontSee('<html')
                ->assertSeeHtml('name="modal_form" value="' . $modalForm . '"');
        }

        $this->actingAs($admin)
            ->get(route('admin.users.edit', ['user' => $user, 'modal' => 1]))
            ->assertSeeHtml('action="' . route('admin.users.verify', [$user, 'modal' => 1]) . '"');
    }

    public function test_modal_validation_reopens_the_matching_admin_form(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $package = Package::factory()->create(['is_selectable' => false]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'modal-validation-instance',
            'ip_address' => '10.0.0.12',
            'api_key_hash' => 'secret',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Modal User',
                'email' => 'modal-user@example.test',
                'password' => 'short',
                'role' => UserRole::REGULAR->value,
                'modal_form' => 'admin-user-create',
            ])
            ->assertRedirect(route('admin.users.index', ['modal' => 'admin-user-create']))
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role' => UserRole::REGULAR->value,
                'package_id' => 'missing-package',
                'modal_form' => 'admin-user-edit',
            ])
            ->assertRedirect(route('admin.users.index', ['modal' => 'admin-user-edit', 'user' => $user]))
            ->assertSessionHasErrors('package_id');

        $this->actingAs($admin)
            ->post(route('admin.packages.store'), [
                'monitoring_limit' => 0,
                'price' => '-1',
                'modal_form' => 'admin-package-create',
            ])
            ->assertRedirect(route('admin.packages.index', ['modal' => 'admin-package-create']))
            ->assertSessionHasErrors(['monitoring_limit', 'price']);

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                'monitoring_limit' => 0,
                'price' => '-1',
                'modal_form' => 'admin-package-edit',
            ])
            ->assertRedirect(route('admin.packages.index', ['modal' => 'admin-package-edit', 'package' => $package]));

        $this->actingAs($admin)
            ->post(route('admin.server-instances.store'), [
                'code' => 'modal-invalid-instance',
                'ip_address' => 'not-an-ip',
                'api_key' => '1234567890abcdef',
                'modal_form' => 'admin-server-instance-create',
            ])
            ->assertRedirect(route('admin.server-instances.index', ['modal' => 'admin-server-instance-create']))
            ->assertSessionHasErrors('ip_address');

        $this->actingAs($admin)
            ->put(route('admin.server-instances.update', $serverInstance), [
                'code' => $serverInstance->code,
                'ip_address' => 'not-an-ip',
                'api_key' => '',
                'modal_form' => 'admin-server-instance-edit',
            ])
            ->assertRedirect(route('admin.server-instances.index', [
                'modal' => 'admin-server-instance-edit',
                'server_instance' => $serverInstance,
            ]));
    }

    public function test_admin_can_verify_email_from_the_user_modal(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.verify', [$user, 'modal' => 1]))
            ->assertRedirect(route('admin.users.index', [
                'modal' => 'admin-user-edit',
                'user' => $user,
            ]))
            ->assertSessionHas('success', __('user.messages.user_verified'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_non_admin_users_cannot_open_admin_form_modals(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.create', ['modal' => 1]))
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        Package::factory()->create();

        return User::factory()->create(['role' => UserRole::ADMIN]);
    }
}
