<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AuthEntryPointsTest extends TestCase
{
    public function test_login_page_has_register_and_demo_credentials_buttons(): void
    {
        $testResponse = $this->get(route('login'));

        $testResponse->assertOk();
        $testResponse->assertSee('href="' . route('register') . '"', false);
        $testResponse->assertSee('href="' . route('login', ['guest' => 'true']) . '"', false);
    }

    public function test_register_page_has_secondary_login_button(): void
    {
        $testResponse = $this->get(route('register'));

        $testResponse->assertOk();
        $testResponse->assertSee('href="' . route('login') . '"', false);
    }

    public function test_demo_route_is_removed(): void
    {
        $this->get('/demo')->assertNotFound();
    }

    public function test_welcome_secondary_ctas_point_to_guest_login(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSee('href="' . route('login', ['guest' => 'true']) . '"', false);
    }
}
