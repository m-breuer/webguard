<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Jobs\DeleteUser;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_delete_revokes_target_user_access_immediately_and_dispatches_job(): void
    {
        Queue::fake();
        Package::factory()->create();

        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user = User::factory()->create([
            'github_id' => '12345',
            'github_token' => 'token',
            'github_refresh_token' => 'refresh-token',
        ]);
        $originalEmail = $user->email;

        $user->createToken('api-access');
        DB::table('sessions')->insert([
            'id' => 'session-' . $user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $originalEmail,
            'token' => 'token',
            'created_at' => now(),
        ]);

        $testResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $testResponse->assertRedirect(route('admin.users.index'));
        $testResponse->assertSessionHas('success', __('user.messages.user_deleted'));
        $this->assertAuthenticatedAs($admin);

        Queue::assertPushed(DeleteUser::class, function (DeleteUser $job) use ($user): bool {
            return $job->user->is($user);
        });

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $originalEmail]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);

        $user->refresh();

        $this->assertNotSame($originalEmail, $user->email);
        $this->assertStringEndsWith('@webguard.invalid', $user->email);
        $this->assertFalse(Hash::check('password', (string) $user->password));
        $this->assertNull($user->remember_token);
        $this->assertNull($user->github_id);
        $this->assertNull($user->github_token);
        $this->assertNull($user->github_refresh_token);

        $this->actingAs($admin)->post(route('logout'));
        $loginAttempt = $this->post(route('login'), [
            'email' => $originalEmail,
            'password' => 'password',
        ]);

        $loginAttempt->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_delete_self(): void
    {
        Queue::fake();
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $testResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $testResponse->assertRedirect(route('admin.users.index'));
        $testResponse->assertSessionHas('error', __('user.messages.cannot_delete_self'));
        Queue::assertNothingPushed();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
