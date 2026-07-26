<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExternalApiRouteBoundaryTest extends TestCase
{
    public function test_every_external_v1_route_uses_an_external_controller_or_adapter(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'v1.'))
            ->reject(fn ($route): bool => str_starts_with($route->uri(), 'api/v1/internal/'))
            ->reject(fn ($route): bool => $route->getName() === 'v1.server-health.store');

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $this->assertStringStartsWith(
                'App\\Http\\Controllers\\Api\\External\\',
                $route->getActionName(),
                $route->uri() . ' must stay behind the external API boundary.'
            );
        }
    }
}
