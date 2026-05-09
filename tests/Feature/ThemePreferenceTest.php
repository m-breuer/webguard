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
        $testResponse->assertSeeHtml('data-theme="system"');
    }

    public function test_sessionless_welcome_page_uses_system_theme_for_authenticated_light_theme_user(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'light',
        ]);

        $testResponse = $this->actingAs($user)
            ->withSession(['theme' => 'dark'])
            ->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-theme="system"');
    }

    public function test_sessionless_welcome_page_uses_system_theme_for_authenticated_dark_theme_user(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'dark',
        ]);

        $testResponse = $this->actingAs($user)
            ->withSession(['theme' => 'light'])
            ->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('class="" data-theme="system"');
    }
}
