<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class FirstPartyUiRouteBoundaryTest extends TestCase
{
    public function test_first_party_routes_use_the_shared_api_namespace_and_named_application_contract(): void
    {
        $routes = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/'))
            ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'app.'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $name = $route->getName();
            $action = $route->getActionName();

            $this->assertNotNull($name, $route->uri() . ' must have a stable first-party route name.');
            $this->assertStringStartsWith('app.', $name);
            $this->assertStringNotContainsString('/v1/', $route->uri());
            $this->assertStringNotContainsString('/internal/', $route->uri());
            $this->assertStringNotContainsString('/ui/', $route->uri());
            $this->assertStringNotContainsString('.v1.', $name);
            $this->assertStringNotContainsString('.internal.', $name);
            $this->assertStringNotContainsString('.ui.', $name);
            $this->assertFalse(str_starts_with($action, 'App\\Http\\Controllers\\Api\\Internal\\Instances\\'));
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
            '/api/*',
            '/api/public/status/{status}',
            '/badge.js',
        ] as $expectedContent) {
            $this->assertStringContainsString($expectedContent, $catalogue);
        }
    }

    public function test_browser_sessions_and_external_tokens_share_standard_resource_contracts(): void
    {
        config()->set('external_api.enforce_token_abilities', true);

        Package::factory()->create();
        $user = User::factory()->create();

        $token = $user->createToken('external-contract', ['external:read'])->plainTextToken;

        foreach (['app.monitorings.index', 'app.teams.index', 'app.status-pages.index'] as $routeName) {
            $browserResponse = $this->actingAs($user)
                ->getJson(route($routeName))
                ->assertOk();

            $tokenResponse = $this->withToken($token)
                ->getJson(route($routeName))
                ->assertOk();

            $this->assertSame(
                array_keys((array) $browserResponse->json()),
                array_keys((array) $tokenResponse->json()),
                $routeName . ' must have the same top-level contract for browser sessions and standard tokens.',
            );
        }
    }
}
