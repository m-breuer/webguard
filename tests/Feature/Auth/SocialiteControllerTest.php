<?php

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        $response = $this->get(route('github.redirect'));

        $this->assertStringContainsString('https://github.com/login/oauth/authorize', $response->getTargetUrl());
    }

    public function test_handle_github_callback_for_new_user(): void
    {
        $githubUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $githubUser->shouldReceive('getId')->andReturn('12345');
        $githubUser->shouldReceive('getName')->andReturn('Test User');
        $githubUser->shouldReceive('getEmail')->andReturn('test@webguard.io');
        $githubUser->shouldReceive('getAvatar')->andReturn('https://avatar.url');
        $githubUser->token = 'test-token';
        $githubUser->refreshToken = 'test-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $response = $this->get(route('github.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'test@webguard.io',
            'github_id' => '12345',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_handle_github_callback_for_existing_socialite_user(): void
    {
        $user = User::factory()->create(['github_id' => '12345']);

        $githubUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $githubUser->shouldReceive('getId')->andReturn('12345');
        $githubUser->shouldReceive('getName')->andReturn($user->name);
        $githubUser->shouldReceive('getEmail')->andReturn($user->email);
        $githubUser->token = 'new-token';
        $githubUser->refreshToken = 'new-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $response = $this->get(route('github.callback'));

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_handle_github_callback_for_existing_local_user(): void
    {
        $user = User::factory()->create(['email' => 'test@webguard.io', 'github_id' => null]);

        $githubUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $githubUser->shouldReceive('getId')->andReturn('12345');
        $githubUser->shouldReceive('getName')->andReturn($user->name);
        $githubUser->shouldReceive('getEmail')->andReturn($user->email);
        $githubUser->token = 'test-token';
        $githubUser->refreshToken = 'test-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $response = $this->get(route('github.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'test@webguard.io',
            'github_id' => '12345',
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_handle_github_callback_with_null_email(): void
    {
        $githubUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $githubUser->shouldReceive('getId')->andReturn('12345');
        $githubUser->shouldReceive('getName')->andReturn('Test User');
        $githubUser->shouldReceive('getEmail')->andReturn(null);

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $response = $this->get(route('github.callback'));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }

    public function test_handle_github_callback_with_empty_string_email(): void
    {
        $githubUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $githubUser->shouldReceive('getId')->andReturn('12345');
        $githubUser->shouldReceive('getName')->andReturn('Test User');
        $githubUser->shouldReceive('getEmail')->andReturn('');

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $response = $this->get(route('github.callback'));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }
}
