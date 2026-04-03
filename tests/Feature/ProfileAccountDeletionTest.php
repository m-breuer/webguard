<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DeleteUser;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProfileAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_delete_logs_user_out_and_dispatches_queued_user_deletion(): void
    {
        Queue::fake();
        Package::factory()->create();

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

        $testResponse = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $testResponse->assertRedirect('/');
        $this->assertGuest();

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

        $loginAttempt = $this->post('/login', [
            'email' => $originalEmail,
            'password' => 'password',
        ]);

        $loginAttempt->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
