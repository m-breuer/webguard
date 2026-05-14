<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoLoginCredentialsTest extends TestCase
{
    public function test_demo_login_credentials_route_returns_demo_user_email(): void
    {
        Package::factory()->create();
        $demoUser = User::factory()->create([
            'email' => 'demo@example.test',
            'role' => UserRole::DEMO,
        ]);

        $testResponse = $this->getJson(route('demo-login.credentials'));

        $testResponse->assertOk()
            ->assertJson(['email' => $demoUser->email]);
    }

    public function test_legacy_guest_login_credentials_route_still_returns_demo_user_email(): void
    {
        Package::factory()->create();
        $demoUser = User::factory()->create([
            'email' => 'demo@example.test',
            'role' => UserRole::DEMO,
        ]);

        $testResponse = $this->getJson(route('guest-login.credentials'));

        $testResponse->assertOk()
            ->assertJson(['email' => $demoUser->email]);
    }

    public function test_guest_role_migration_renames_existing_demo_account_history(): void
    {
        $package = Package::factory()->create();

        DB::table('users')->insert([
            'id' => (string) Str::ulid(),
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'guest',
            'package_id' => $package->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_05_14_120000_rename_guest_role_to_demo.php');
        $migration->up();

        $this->assertDatabaseHas('users', [
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'role' => UserRole::DEMO->value,
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'guest@example.com',
            'role' => 'guest',
        ]);
    }
}
