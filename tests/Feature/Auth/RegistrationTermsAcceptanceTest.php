<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RegistrationTermsAcceptanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create([
            'price' => 0,
            'is_selectable' => true,
        ]);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $registrationData = $this->validRegistrationData();

        $testResponse = $this->post('/register', $registrationData);

        $testResponse->assertSessionHasErrors('terms');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => $registrationData['email']]);
    }

    public function test_registration_stores_terms_accepted_timestamp(): void
    {
        $registrationData = [
            ...$this->validRegistrationData(),
            'terms' => '1',
        ];

        $testResponse = $this->post('/register', $registrationData);

        $testResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::query()->where('email', $registrationData['email'])->first();

        $this->assertNotNull($user);
        $this->assertInstanceOf(Carbon::class, $user->terms_accepted_at);
        $this->assertInstanceOf(Carbon::class, $user->privacy_accepted_at);
    }

    /**
     * @return array<string, string>
     */
    private function validRegistrationData(): array
    {
        return [
            'name' => 'Terms Acceptance Tester',
            'email' => 'terms-acceptance@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
    }
}
