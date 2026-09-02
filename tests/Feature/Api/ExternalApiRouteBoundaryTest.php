<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExternalApiRouteBoundaryTest extends TestCase
{
    public function test_runtime_api_routes_do_not_expose_legacy_namespace_segments(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $this->assertStringNotContainsString('/v1/', $route->uri());
            $this->assertStringNotContainsString('/internal/', $route->uri());
            $this->assertStringNotContainsString('/external/', $route->uri());
            $this->assertStringNotContainsString('/ui/', $route->uri());
            $this->assertStringNotContainsString('.v1.', (string) $route->getName());
            $this->assertStringNotContainsString('.internal.', (string) $route->getName());
            $this->assertStringNotContainsString('.external.', (string) $route->getName());
            $this->assertStringNotContainsString('.ui.', (string) $route->getName());
        }
    }
}
