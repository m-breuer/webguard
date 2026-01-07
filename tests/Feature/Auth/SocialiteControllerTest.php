<?php

namespace Tests\Feature\Auth;

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

    public function test_handle_github_callback_for_new_user(): void
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

        $this->assertDatabaseHas('users', [
            'email' => 'test@webguard.io',
            'github_id' => '12345',
        ]);

        $this->assertAuthenticated();
        $testResponse->assertRedirect(route('dashboard'));
    }

    public function test_handle_github_callback_for_existing_socialite_user(): void
    {
        $user = User::factory()->create(['github_id' => '12345']);

        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn($user->name);
        $mock->shouldReceive('getEmail')->andReturn($user->email);
        $mock->token = 'new-token';
        $mock->refreshToken = 'new-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
        $testResponse->assertRedirect(route('dashboard'));
    }

    public function test_handle_github_callback_for_existing_local_user(): void
    {
        $user = User::factory()->create(['email' => 'test@webguard.io', 'github_id' => null]);

        $mock = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn($user->name);
        $mock->shouldReceive('getEmail')->andReturn($user->email);
        $mock->token = 'test-token';
        $mock->refreshToken = 'test-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($mock);

        $testResponse = $this->get(route('github.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'test@webguard.io',
            'github_id' => '12345',
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
        $testResponse->assertRedirect(route('dashboard'));
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
}
