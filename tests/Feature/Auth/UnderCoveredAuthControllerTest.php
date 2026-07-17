<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UnderCoveredAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_confirm_password_view_validation_and_success_redirect(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->actingAs($user)->get(route('password.confirm'))->assertOk();

        $this->actingAs($user)->post(route('password.confirm'), [
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->post(route('password.confirm'), [
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_email_verification_notification_redirects_verified_users_and_sends_for_unverified_users(): void
    {
        Notification::fake();

        $verifiedUser = User::factory()->create();
        $this->actingAs($verifiedUser)
            ->post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        $unverifiedUser = User::factory()->unverified()->create();
        $this->actingAs($unverifiedUser)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($unverifiedUser, VerifyEmail::class);
    }

    public function test_verification_prompt_and_verify_email_controller_paths(): void
    {
        Event::fake([Verified::class]);

        $verifiedUser = User::factory()->create();
        $this->actingAs($verifiedUser)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard', absolute: false));

        $unverifiedUser = User::factory()->unverified()->create();
        $this->actingAs($unverifiedUser)
            ->get(route('verification.notice'))
            ->assertOk();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $unverifiedUser->id, 'hash' => sha1($unverifiedUser->email)],
            false
        );

        $this->actingAs($unverifiedUser)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false) . '?verified=1');

        $this->assertTrue($unverifiedUser->refresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);

        $alreadyVerifiedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $verifiedUser->id, 'hash' => sha1($verifiedUser->email)],
            false
        );

        $this->actingAs($verifiedUser)
            ->get($alreadyVerifiedUrl)
            ->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
    }

    public function test_demo_login_credentials_returns_not_found_without_demo_user(): void
    {
        $this->getJson(route('demo-login.credentials'))
            ->assertNotFound()
            ->assertJson(['error' => __('auth.guest_login.no_guest_user_found')]);

        User::factory()->create([
            'role' => UserRole::DEMO,
            'email' => 'demo@example.com',
        ]);

        $this->getJson(route('demo-login.credentials'))
            ->assertOk()
            ->assertJson(['email' => 'demo@example.com']);
    }

    public function test_login_request_dispatches_lockout_after_too_many_attempts(): void
    {
        Event::fake([Lockout::class]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => 'locked@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), [
            'email' => 'locked@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        Event::assertDispatched(Lockout::class);
    }
}
