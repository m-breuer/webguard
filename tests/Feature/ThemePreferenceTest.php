<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_uses_system_theme_even_if_theme_session_value_exists(): void
    {
        $testResponse = $this->withSession(['theme' => 'dark'])->get('/');

        $testResponse->assertOk();
        $testResponse->assertSee('data-theme="system"', false);
    }

    public function test_authenticated_user_theme_comes_from_database_and_not_session(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'light',
        ]);

        $testResponse = $this->actingAs($user)
            ->withSession(['theme' => 'dark'])
            ->get('/');

        $testResponse->assertOk();
        $testResponse->assertSee('data-theme="light"', false);
    }

    public function test_authenticated_dark_theme_from_database_sets_dark_class(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'dark',
        ]);

        $testResponse = $this->actingAs($user)
            ->withSession(['theme' => 'light'])
            ->get('/');

        $testResponse->assertOk();
        $testResponse->assertSee('class="dark" data-theme="dark"', false);
    }
}
