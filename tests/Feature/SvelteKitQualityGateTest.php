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
        $this->assertStringContainsString('php frontend gateway schedule queue-default mysql redis', $script);
        $this->assertStringContainsString('/_health/gateway', $script);
        $this->assertStringContainsString('/_health/frontend', $script);
        $this->assertStringContainsString('/_health/laravel', $script);
        $this->assertStringContainsString('healthcheck-queue', $script);
        $this->assertStringContainsString('php artisan schedule:list', $script);
        $this->assertStringContainsString('down --volumes --remove-orphans', $script);
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
}
