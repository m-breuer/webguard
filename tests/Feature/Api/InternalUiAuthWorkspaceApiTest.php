<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class InternalUiAuthWorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_guest_can_load_options_sign_in_and_register(): void
    {
        Notification::fake();
        $member = User::factory()->create([
            'email' => 'member@example.test',
            'password' => Hash::make('correct-password'),
        ]);
        $demo = User::factory()->create([
            'role' => UserRole::DEMO,
            'email' => 'demo@example.test',
        ]);

        $this->getJson(route('api.v1.internal.ui.auth.options'))
            ->assertOk()
            ->assertJsonPath('data.captcha_url', url('captcha/register'))
            ->assertJsonPath('data.terms_url', config('app.marketing_url') . '/terms-of-use');
        $this->getJson(route('api.v1.internal.ui.auth.demo-credentials'))
            ->assertOk()
            ->assertJsonPath('data.email', $demo->email);
        $this->postJson(route('api.v1.internal.ui.auth.login'), [
            'email' => $member->email,
            'password' => 'correct-password',
        ])->assertOk()->assertJsonPath('data.next_url', '/dashboard');

        $this->assertAuthenticatedAs($member);
        auth()->logout();

        $this->postJson(route('api.v1.internal.ui.auth.register'), [
            'name' => 'New member',
            'email' => 'new-member@example.test',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
            'terms' => true,
            'captcha' => $this->validCaptchaValue(),
        ])->assertCreated()->assertJsonPath('data.next_url', '/verify-email');

        $model = User::query()->where('email', 'new-member@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($model);
        Notification::assertSentTo($model, VerifyEmail::class);
    }

    public function test_guest_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->postJson(route('api.v1.internal.ui.auth.password.email'), ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('data.message', __(Password::RESET_LINK_SENT));
        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::broker()->createToken($user);
        $this->postJson(route('api.v1.internal.ui.auth.password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()->assertJsonPath('data.next_url', '/login');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_authenticated_user_can_resend_verification_and_confirm_password(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['password' => Hash::make('correct-password')]);

        $this->actingAs($user)
            ->postJson(route('api.v1.internal.ui.auth.verification.send'))
            ->assertOk()
            ->assertJsonPath('data.verification_required', true);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->actingAs($user)
            ->postJson(route('api.v1.internal.ui.auth.password.confirm'), ['password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
        $this->actingAs($user)
            ->postJson(route('api.v1.internal.ui.auth.password.confirm'), ['password' => 'correct-password'])
            ->assertOk()
            ->assertJsonPath('data.next_url', '/dashboard');

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }
}
