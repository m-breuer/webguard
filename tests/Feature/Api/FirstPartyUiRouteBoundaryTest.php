<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class FirstPartyUiRouteBoundaryTest extends TestCase
{
    public function test_first_party_ui_routes_stay_versioned_named_and_separate_from_public_api_consumers(): void
    {
        $routes = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1/internal/ui/'));

        $this->assertCount(94, $routes);

        foreach ($routes as $route) {
            $name = $route->getName();
            $action = $route->getActionName();

            $this->assertNotNull($name, $route->uri() . ' must have a stable first-party route name.');
            $this->assertStringStartsWith('api.v1.internal.ui.', $name);
            $this->assertFalse(str_starts_with($action, 'App\\Http\\Controllers\\Api\\External\\'));
            $this->assertFalse(str_starts_with($action, 'App\\Http\\Controllers\\Api\\Internal\\Instances\\'));
            $this->assertStringNotContainsString('ApiController@', $action);
        }
    }

    public function test_first_party_contract_catalogue_describes_transport_authentication_and_public_boundaries(): void
    {
        $catalogue = file_get_contents(base_path('docs/architecture/first-party-ui-contract.md'));

        $this->assertIsString($catalogue);

        foreach ([
            '## Transport rules',
            '## Session and guest authentication',
            '## Account bootstrap and settings',
            '## Verified member workspace',
            '## Teams and status-page operations',
            '## Administration',
            '## Public compatibility boundary',
            '/api/v1/internal/ui/*',
            '/api/public/status/{status}',
            '/badge.js',
        ] as $expectedContent) {
            $this->assertStringContainsString($expectedContent, $catalogue);
        }
    }
}
