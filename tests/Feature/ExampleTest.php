<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_it_returns_a_successful_response(): void
    {
        $testResponse = $this->get('/');

        $testResponse->assertOk();
    }
}
