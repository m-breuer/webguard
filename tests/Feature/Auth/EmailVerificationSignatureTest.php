<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_link_uses_relative_signature_and_remains_valid_on_different_host(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $relativeSignedPath = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
            absolute: false
        );

        $testResponse = $this->actingAs($user)
            ->get('https://www.webguard.m-breuer.dev' . $relativeSignedPath);

        $testResponse->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
