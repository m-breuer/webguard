<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Package;
use App\Models\User;
use App\Services\UserDeletionPreparationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserDeletionPreparationServiceTest extends TestCase
{
    public function test_disable_login_until_deletion_revokes_all_immediate_login_paths(): void
    {
        $service = app(UserDeletionPreparationService::class);
        Package::factory()->create();
        $user = User::factory()->create([
            'github_id' => '12345',
            'github_token' => 'token',
            'github_refresh_token' => 'refresh-token',
            'remember_token' => 'remember-me',
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

        $service->disableLoginUntilDeletion($user);

        $user->refresh();

        $this->assertSame(sprintf('deleted+%s@webguard.invalid', strtolower($user->id)), $user->email);
        $this->assertFalse(Hash::check('password', (string) $user->password));
        $this->assertNull($user->remember_token);
        $this->assertNull($user->github_id);
        $this->assertNull($user->github_token);
        $this->assertNull($user->github_refresh_token);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $originalEmail]);
    }
}
