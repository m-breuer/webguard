<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $testResponse = $this->get('/');

        $testResponse->assertRedirect(route('login', absolute: false));
    }
}
