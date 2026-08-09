<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_uses_system_theme_even_if_theme_session_value_exists(): void
    {
        $testResponse = $this->withSession(['theme' => 'dark'])->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-theme="system"');
    }

    public function test_theme_bootstrap_applies_system_dark_mode_before_assets_load(): void
    {
        $testResponse = $this->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('<meta name="color-scheme" content="light dark">');
        $testResponse->assertSeeHtml("html.classList.toggle('dark', isDark);");
        $testResponse->assertSeeHtml('html.dark {');
        $testResponse->assertSeeHtml('background-color: #020617;');
    }
}
