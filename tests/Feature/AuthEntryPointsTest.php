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
        $testResponse->assertSeeHtml('href="' . route('register') . '"');
        $testResponse->assertSeeHtml('href="' . route('login', ['guest' => 'true']) . '"');
    }

    public function test_register_page_has_secondary_login_button(): void
    {
        $testResponse = $this->get(route('register'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('href="' . route('login') . '"');
    }

    public function test_demo_route_is_removed(): void
    {
        $this->get('/demo')->assertNotFound();
    }

    public function test_welcome_secondary_ctas_point_to_guest_login(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('href="' . route('login', ['guest' => 'true']) . '"');
    }
}
