<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\SocialiteController;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialiteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create(['price' => 0, 'is_selectable' => true]);
    }

    public function test_redirect_to_github(): void
    {
        $testResponse = $this->get(route('github.redirect'));

        $this->assertStringContainsString('https://github.com/login/oauth/authorize', $testResponse->getTargetUrl());
    }

    public function test_handle_github_callback_for_new_user_requires_legal_consent_and_creates_user_after_acceptance(): void
    {
        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn('Test User');
        $mock->shouldReceive('getEmail')->andReturn('test@webguard.io');
        $mock->shouldReceive('getAvatar')->andReturn('https://avatar.url');
        $mock->token = 'test-token';
        $mock->refreshToken = 'test-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $testResponse->assertRedirect(route('github.consent.create'));
        $testResponse->assertSessionHas(SocialiteController::SESSION_PENDING_GITHUB_USER);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@webguard.io']);

        $consentResponse = $this->post(route('github.consent.store'), [
            'terms' => '1',
        ]);

        $consentResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@webguard.io',
            'github_id' => '12345',
        ]);

        $user = User::query()->where('email', 'test@webguard.io')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_accepted_at);
    }

    public function test_handle_github_callback_for_existing_socialite_user_with_consent_logs_in_directly(): void
    {
        $user = User::factory()->create([
            'github_id' => '12345',
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
        ]);

        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn($user->name);
        $mock->shouldReceive('getEmail')->andReturn($user->email);
        $mock->shouldReceive('getAvatar')->andReturn('https://avatar.url');
        $mock->token = 'new-token';
        $mock->refreshToken = 'new-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
        $testResponse->assertRedirect(route('dashboard'));
    }

    public function test_handle_github_callback_for_existing_socialite_user_without_consent_redirects_to_consent(): void
    {
        $user = User::factory()->create(['github_id' => '12345']);

        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn($user->name);
        $mock->shouldReceive('getEmail')->andReturn($user->email);
        $mock->shouldReceive('getAvatar')->andReturn('https://avatar.url');
        $mock->token = 'new-token';
        $mock->refreshToken = 'new-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $testResponse->assertRedirect(route('github.consent.create'));
        $testResponse->assertSessionHas(SocialiteController::SESSION_PENDING_EXISTING_USER_ID, $user->id);
        $this->assertGuest();
    }

    public function test_handle_github_callback_for_existing_local_user_requires_consent_then_links_account(): void
    {
        $user = User::factory()->create(['email' => 'test@webguard.io', 'github_id' => null]);

        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn($user->name);
        $mock->shouldReceive('getEmail')->andReturn($user->email);
        $mock->shouldReceive('getAvatar')->andReturn('https://avatar.url');
        $mock->token = 'test-token';
        $mock->refreshToken = 'test-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $testResponse->assertRedirect(route('github.consent.create'));
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();

        $consentResponse = $this->post(route('github.consent.store'), [
            'terms' => '1',
        ]);

        $consentResponse->assertRedirect(route('dashboard'));
        $user->refresh();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('12345', $user->github_id);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_accepted_at);
    }

    public function test_handle_github_callback_with_null_email(): void
    {
        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn('Test User');
        $mock->shouldReceive('getEmail')->andReturn(null);

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $testResponse->assertRedirect(route('register'));
        $testResponse->assertSessionHasErrors('socialite_error');
    }

    public function test_handle_github_callback_with_empty_string_email(): void
    {
        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn('Test User');
        $mock->shouldReceive('getEmail')->andReturn('');

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $testResponse->assertRedirect(route('register'));
        $testResponse->assertSessionHasErrors('socialite_error');
    }

    public function test_github_consent_routes_require_pending_session_state(): void
    {
        $this->get(route('github.consent.create'))->assertRedirect(route('login'));
        $this->post(route('github.consent.store'), ['terms' => '1'])->assertRedirect(route('login'));
    }

    public function test_github_consent_submission_requires_terms_acceptance(): void
    {
        $testResponse = $this
            ->from(route('github.consent.create'))
            ->withSession([
                SocialiteController::SESSION_PENDING_GITHUB_USER => [
                    'name' => 'Test User',
                    'email' => 'test@webguard.io',
                    'github_id' => '12345',
                    'github_token' => 'test-token',
                    'github_refresh_token' => 'test-refresh-token',
                    'avatar' => 'https://avatar.url',
                ],
            ])
            ->post(route('github.consent.store'));

        $testResponse->assertRedirect(route('github.consent.create'));
        $testResponse->assertSessionHasErrors('terms');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@webguard.io']);
    }
}
