<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email_and_password(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'email' => 'marcel@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $testResponse = $this->postJson('/api/mobile/login', [
            'email' => 'MARCEL@example.com',
            'password' => 'correct-password',
            'device_name' => 'Marcel iPhone',
        ]);

        $testResponse
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'marcel@example.com')
            ->assertJsonMissingPath('data.user.password');

        $this->assertIsString($testResponse->json('data.token'));
        $this->assertStringContainsString('|', $testResponse->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'ios-app:Marcel iPhone',
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        Package::factory()->create();
        User::factory()->create([
            'email' => 'marcel@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => 'marcel@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_mobile_user_can_read_profile_and_logout(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'email' => 'marcel@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $token = $user->createToken('ios-app:Marcel iPhone')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'marcel@example.com');

        $this->withToken($token)
            ->postJson('/api/mobile/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'ios-app:Marcel iPhone',
        ]);
    }
}
