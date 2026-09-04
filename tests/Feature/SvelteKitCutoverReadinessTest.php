<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SvelteKitCutoverReadinessTest extends TestCase
{
    public function test_gateway_keeps_sveltekit_primary_with_laravel_compatibility_boundaries(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));

        $this->assertIsString($gatewayConfiguration);
        $this->assertStringContainsString('location = /status', $gatewayConfiguration);
        $this->assertStringContainsString('location = /badge.js', $gatewayConfiguration);
        $this->assertStringContainsString('location ^~ /api/', $gatewayConfiguration);
        $this->assertStringContainsString('location ^~ /sanctum/', $gatewayConfiguration);
        $this->assertStringContainsString('proxy_pass http://sveltekit', $gatewayConfiguration);
        $this->assertStringContainsString('error_page 404 = @laravel', $gatewayConfiguration);
        $this->assertStringContainsString('location @laravel', $gatewayConfiguration);
    }

    public function test_cutover_route_inventory_has_sveltekit_pages_for_supported_browser_workflows(): void
    {
        $routes = [
            'frontend/src/routes/login/+page.svelte',
            'frontend/src/routes/register/+page.svelte',
            'frontend/src/routes/forgot-password/+page.svelte',
            'frontend/src/routes/reset-password/[token]/+page.svelte',
            'frontend/src/routes/verify-email/+page.svelte',
            'frontend/src/routes/(app)/dashboard/+page.svelte',
            'frontend/src/routes/(app)/monitorings/+page.svelte',
            'frontend/src/routes/(app)/monitorings/[id]/+page.svelte',
            'frontend/src/routes/(app)/maintenance/+page.svelte',
            'frontend/src/routes/(app)/monitoring-groups/+page.svelte',
            'frontend/src/routes/(app)/status-pages/+page.svelte',
            'frontend/src/routes/(app)/teams/+page.svelte',
            'frontend/src/routes/(app)/notifications/+page.svelte',
            'frontend/src/routes/(app)/profile/+page.svelte',
            'frontend/src/routes/(app)/admin/+page.svelte',
            'frontend/src/routes/status/[id]/+page.svelte',
        ];

        foreach ($routes as $route) {
            $this->assertFileExists(base_path($route));
        }
    }

    public function test_cutover_runbook_keeps_blade_retirement_separate_from_production_rollout(): void
    {
        $runbook = file_get_contents(base_path('docs/operations/sveltekit-cutover.md'));

        $this->assertIsString($runbook);
        $this->assertStringContainsString('## Staging and canary checklist', $runbook);
        $this->assertStringContainsString('## Rollback', $runbook);
        $this->assertStringContainsString('## Blade and Alpine retirement gate', $runbook);
        $this->assertStringContainsString('error_page 404 = @laravel', $runbook);
    }
}
