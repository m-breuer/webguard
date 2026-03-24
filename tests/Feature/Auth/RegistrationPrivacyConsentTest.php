<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationPrivacyConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create(['price' => 0, 'is_selectable' => true]);
    }

    public function test_registration_fails_when_privacy_policy_is_not_accepted(): void
    {
        $testResponse = $this->from(route('login', ['mode' => 'register']))->post(route('register'), [
            'form_mode' => 'register',
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $testResponse->assertRedirect(route('login', ['mode' => 'register']));
        $testResponse->assertSessionHasErrors('privacy');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'jane@example.test',
        ]);
    }

    public function test_registration_stores_privacy_accepted_timestamp_after_acceptance(): void
    {
        $testResponse = $this->post(route('register'), [
            'form_mode' => 'register',
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
            'privacy' => '1',
        ]);

        $testResponse->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'jane@example.test')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertNotNull($user->terms_accepted_at);
    }
}
