<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GitHubSignInRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_github_oauth_routes_configuration_and_credentials_are_removed_while_password_login_remains_available(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse(Route::has('github.redirect'));
        $this->assertFalse(Route::has('github.callback'));
        $this->assertFalse(Route::has('github.consent.create'));
        $this->assertFalse(Route::has('github.consent.store'));
        $this->assertNull(config('services.github'));
        $this->assertNotContains('github_id', Schema::getColumnListing('users'));
        $this->assertNotContains('github_token', Schema::getColumnListing('users'));
        $this->assertNotContains('github_refresh_token', Schema::getColumnListing('users'));

        $this->get('/auth/github/redirect')->assertNotFound();
        $this->get('/auth/github/callback')->assertNotFound();
        $this->get('/auth/github/consent')->assertNotFound();
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('GitHub');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
