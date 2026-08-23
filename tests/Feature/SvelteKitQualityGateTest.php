<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SvelteKitQualityGateTest extends TestCase
{
    public function test_ci_runs_sveltekit_checks_budgets_and_container_smoke_test(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('bun run frontend:check', $workflow);
        $this->assertStringContainsString('bun run frontend:build', $workflow);
        $this->assertStringContainsString('bun run --cwd frontend budget', $workflow);
        $this->assertStringContainsString('Run SvelteKit container topology smoke test', $workflow);
        $this->assertStringContainsString('.github/scripts/smoke-sveltekit-topology.sh', $workflow);
    }

    public function test_topology_smoke_test_isolated_runtime_services_and_checks_all_health_boundaries(): void
    {
        $script = file_get_contents(base_path('.github/scripts/smoke-sveltekit-topology.sh'));

        $this->assertIsString($script);
        $this->assertStringContainsString('--profile internal-services', $script);
        $this->assertStringContainsString('docker network create', $script);
        $this->assertStringContainsString('build php frontend gateway queue-default', $script);
        $this->assertStringContainsString('up --no-build --detach --wait', $script);
        $this->assertStringContainsString('php frontend gateway schedule queue-default mysql redis', $script);
        $this->assertStringContainsString('/_health/gateway', $script);
        $this->assertStringContainsString('/_health/frontend', $script);
        $this->assertStringContainsString('/_health/laravel', $script);
        $this->assertStringContainsString('healthcheck-queue', $script);
        $this->assertStringContainsString('php artisan schedule:list', $script);
        $this->assertStringContainsString('db:seed --class=PackageSeeder', $script);
        $this->assertStringContainsString('sveltekit-browser-smoke@example.test', $script);
        $this->assertStringContainsString('SMOKE_UNSUBSCRIBE_TOKEN', $script);
        $this->assertStringContainsString('mcr.microsoft.com/playwright:v1.62.1-noble', $script);
        $this->assertStringContainsString('node_modules:/ms-playwright/node_modules:ro', $script);
        $this->assertStringContainsString('/ms-playwright/smoke/smoke-public-status.mjs', $script);
        $this->assertStringContainsString('smoke-public-status.mjs', $script);
        $this->assertStringContainsString('down --volumes --remove-orphans', $script);
    }

    public function test_browser_smoke_test_checks_the_public_status_page_at_desktop_and_mobile_viewports(): void
    {
        $browserSmokeTest = file_get_contents(base_path('frontend/scripts/smoke-public-status.mjs'));

        $this->assertIsString($browserSmokeTest);
        $this->assertStringContainsString('chromium.launch', $browserSmokeTest);
        $this->assertStringContainsString('width: 1280', $browserSmokeTest);
        $this->assertStringContainsString('width: 390', $browserSmokeTest);
        $this->assertStringContainsString('getByRole("heading"', $browserSmokeTest);
        $this->assertStringContainsString('getByLabel("Email address")', $browserSmokeTest);
        $this->assertStringContainsString('Unsubscribe from updates', $browserSmokeTest);
        $this->assertStringContainsString('subscription=unsubscribed', $browserSmokeTest);
        $this->assertStringContainsString('consoleErrors', $browserSmokeTest);
        $this->assertStringContainsString('noHorizontalOverflow', $browserSmokeTest);
        $this->assertStringContainsString('1000 ms rendering budget', $browserSmokeTest);
    }

    public function test_gateway_and_sveltekit_preserve_safe_request_correlation(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));
        $proxyHeaders = file_get_contents(base_path('docker/gateway/proxy-headers.conf'));
        $svelteHooks = file_get_contents(base_path('frontend/src/hooks.server.ts'));

        $this->assertIsString($gatewayConfiguration);
        $this->assertIsString($proxyHeaders);
        $this->assertIsString($svelteHooks);
        $this->assertStringContainsString('request_id=$request_id', $gatewayConfiguration);
        $this->assertStringContainsString('X-Request-Id $request_id', $proxyHeaders);
        $this->assertStringContainsString('request.headers.set("X-Request-Id", requestId)', $svelteHooks);
    }

    public function test_gateway_serves_canonical_unsubscribe_pages_from_sveltekit(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));
        $unsubscribePage = base_path('frontend/src/routes/status/[id]/subscribers/unsubscribe/[token]/+page.svelte');
        $unsubscribeAction = base_path('frontend/src/routes/status/[id]/subscribers/unsubscribe/[token]/+page.server.ts');

        $this->assertIsString($gatewayConfiguration);
        $this->assertStringContainsString('^/status/[0-9a-hjkmnp-tv-z]{26}/subscribers/unsubscribe/', $gatewayConfiguration);
        $this->assertStringContainsString('proxy_pass http://sveltekit', $gatewayConfiguration);
        $this->assertFileExists($unsubscribePage);
        $this->assertFileExists($unsubscribeAction);
        $this->assertStringContainsString('subscribers/unsubscribe', (string) file_get_contents($unsubscribeAction));
    }
}
