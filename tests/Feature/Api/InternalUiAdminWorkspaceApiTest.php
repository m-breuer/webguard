<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\ApiLog;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

class InternalUiAdminWorkspaceApiTest extends TestCase
{
    use AssertsApiContracts;
    use RefreshDatabase;

    public function test_member_cannot_access_administration_api_contracts(): void
    {
        Package::factory()->create();
        $member = User::factory()->create();

        foreach (['dashboard', 'users.index', 'packages.index', 'server-instances.index', 'api-logs.index', 'activity-logs.index'] as $route) {
            $this->actingAs($member)->getJson(route("api.v1.internal.ui.admin.{$route}"))->assertForbidden();
        }
    }

    public function test_admin_can_manage_users_packages_and_server_instances(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 25, 'is_selectable' => true]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)->postJson(route('api.v1.internal.ui.admin.users.store'), [
            'name' => 'Managed user', 'email' => 'managed@example.test', 'password' => 'new-password', 'role' => UserRole::REGULAR->value, 'package_id' => $package->id,
        ])->assertCreated()->assertJsonPath('data.email', 'managed@example.test');

        $createdPackage = $this->actingAs($admin)->postJson(route('api.v1.internal.ui.admin.packages.store'), [
            'monitoring_limit' => 50, 'price' => 19.99, 'is_selectable' => true,
        ])->assertCreated()->json('data');

        $this->actingAs($admin)->postJson(route('api.v1.internal.ui.admin.server-instances.store'), [
            'code' => 'de-admin-1', 'display_name' => 'Admin Germany', 'country_code' => 'DE', 'region' => 'Frankfurt', 'ip_address' => '192.0.2.42', 'api_key' => 'a-secure-instance-key', 'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.health', 'never_seen');

        $this->actingAs($admin)->getJson(route('api.v1.internal.ui.admin.users.index'))
            ->assertOk()->assertJsonFragment(['email' => 'managed@example.test']);
        $this->actingAs($admin)->getJson(route('api.v1.internal.ui.admin.packages.index'))
            ->assertOk()->assertJsonFragment(['id' => $createdPackage['id']]);
        $this->actingAs($admin)->getJson(route('api.v1.internal.ui.admin.server-instances.index'))
            ->assertOk()->assertJsonFragment(['code' => 'de-admin-1']);

        $this->assertDatabaseHas('server_instances', ['code' => 'de-admin-1']);
        $this->assertInternalUiTelemetry($this->actingAs($admin)->getJson(route('api.v1.internal.ui.admin.dashboard')), 6, 131072);
    }

    public function test_admin_contract_validates_destructive_and_log_workflows(): void
    {
        $assignedPackage = Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $assignedUser = User::factory()->create(['package_id' => $assignedPackage->id]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'de-test-1',
            'display_name' => 'Test Germany',
            'country_code' => 'DE',
            'ip_address' => '192.0.2.41',
            'api_key_hash' => 'a-secure-instance-key',
        ]);
        ApiLog::query()->create(['user_id' => $admin->id, 'route' => '/api/v1/monitorings']);

        $this->actingAs($admin)->deleteJson(route('api.v1.internal.ui.admin.packages.destroy', $assignedPackage))
            ->assertUnprocessable()->assertJsonValidationErrors('package');
        $this->actingAs($admin)->deleteJson(route('api.v1.internal.ui.admin.users.destroy', $admin))
            ->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->actingAs($admin)->deleteJson(route('api.v1.internal.ui.admin.server-instances.destroy', $serverInstance))
            ->assertNoContent();
        $this->actingAs($admin)->getJson(route('api.v1.internal.ui.admin.api-logs.index'))
            ->assertOk()->assertJsonPath('data.items.0.user_email', $admin->email);
        $this->actingAs($admin)->getJson(route('api.v1.internal.ui.admin.activity-logs.index'))->assertOk();

        $this->assertDatabaseHas('users', ['id' => $assignedUser->id]);
    }

    public function test_admin_can_update_users_and_verify_an_email_address(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 50]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->unverified()->create(['email' => 'original@example.test']);

        $this->actingAs($admin)->patchJson(route('api.v1.internal.ui.admin.users.update', $user), [
            'name' => 'Updated user',
            'email' => 'updated@example.test',
            'password' => 'updated-password',
            'role' => UserRole::ADMIN->value,
            'package_id' => $package->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'updated@example.test')
            ->assertJsonPath('data.package_limit', 50);

        $this->assertTrue(Hash::check('updated-password', $user->refresh()->password));

        $this->actingAs($admin)->postJson(route('api.v1.internal.ui.admin.users.verify', $user))
            ->assertOk()
            ->assertJsonPath('data.email_verified_at', $user->refresh()->email_verified_at?->toIso8601String());
    }
}
