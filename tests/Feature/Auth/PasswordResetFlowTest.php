<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    public function test_guest_can_view_forgot_password_screen(): void
    {
        $testResponse = $this->get(route('password.request'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('auth.forgot_password.title'));
    }

    public function test_login_page_exposes_forgot_password_link(): void
    {
        $testResponse = $this->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('href="' . route('password.request') . '"');
    }

    public function test_guest_can_request_a_password_reset_link(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        Notification::fake();

        $testResponse = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $testResponse->assertSessionHas('status', __(Password::RESET_LINK_SENT));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_guest_can_view_password_reset_screen_with_valid_token(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $testResponse = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('auth.reset_password.title'));
        $testResponse->assertSeeHtml('value="' . $user->email . '"');
    }

    public function test_guest_can_reset_password_with_valid_token(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $testResponse = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $testResponse->assertRedirect(route('login'));
        $testResponse->assertSessionHas('status', __(Password::PASSWORD_RESET));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }
}
