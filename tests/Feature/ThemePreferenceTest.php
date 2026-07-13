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

}
