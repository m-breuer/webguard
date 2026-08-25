<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SvelteKitQualityGateTest extends TestCase
{
    public function test_sveltekit_operational_forms_guard_duplicate_submissions(): void
    {
        $singleSubmitComponents = [
            'frontend/src/lib/components/MonitoringForm.svelte',
            'frontend/src/lib/components/MonitoringGroupForm.svelte',
            'frontend/src/lib/components/StatusPageForm.svelte',
            'frontend/src/routes/(app)/maintenance/+page.svelte',
        ];

        foreach ($singleSubmitComponents as $component) {
            $contents = file_get_contents(base_path($component));

            $this->assertIsString($contents);
            $this->assertStringContainsString('if (submitting)', $contents, $component);
        }

        $monitoringOperations = file_get_contents(base_path('frontend/src/lib/components/MonitoringOperationsPanel.svelte'));
        $monitoringGroupForm = file_get_contents(base_path('frontend/src/lib/components/MonitoringGroupForm.svelte'));

        $this->assertIsString($monitoringOperations);
        $this->assertIsString($monitoringGroupForm);
        $this->assertStringContainsString('notificationsSaving)', $monitoringOperations);
        $this->assertStringContainsString('ownershipSaving)', $monitoringOperations);
        $this->assertStringContainsString('await onSuccess()', $monitoringGroupForm);
    }

    public function test_sveltekit_components_use_tailwind_without_scoped_or_inline_styles(): void
    {
        foreach (File::allFiles(base_path('frontend/src')) as $file) {
            if ($file->getExtension() !== 'svelte') {
                continue;
            }

            $contents = $file->getContents();

            $this->assertStringNotContainsString('<style', $contents, $file->getPathname());
            $this->assertDoesNotMatchRegularExpression('/\\sstyle\\s*=/i', $contents, $file->getPathname());
        }
    }

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
        $this->assertStringContainsString('SMOKE_STATUS_PAGE_SLUG', $script);
        $this->assertStringContainsString('SMOKE_UNSUBSCRIBE_TOKEN', $script);
        $this->assertStringContainsString('SMOKE_CONFIRMATION_TOKEN', $script);
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
        $this->assertStringContainsString('javaScriptEnabled: false', $browserSmokeTest);
        $this->assertStringContainsString('sveltekit-browser-subscription@example.test', $browserSmokeTest);
        $this->assertStringContainsString('Check your inbox to confirm your subscription.', $browserSmokeTest);
        $this->assertStringContainsString('consoleErrors', $browserSmokeTest);
        $this->assertStringContainsString('noHorizontalOverflow', $browserSmokeTest);
        $this->assertStringContainsString('1000 ms rendering budget', $browserSmokeTest);
    }

    public function test_gateway_and_sveltekit_preserve_safe_request_correlation(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));
        $proxyHeaders = file_get_contents(base_path('docker/gateway/proxy-headers.conf'));
        $svelteHooks = file_get_contents(base_path('frontend/src/hooks.server.ts'));
        $applicationBootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertIsString($gatewayConfiguration);
        $this->assertIsString($proxyHeaders);
        $this->assertIsString($svelteHooks);
        $this->assertIsString($applicationBootstrap);
        $this->assertStringContainsString('request_id=$request_id', $gatewayConfiguration);
        $this->assertStringContainsString('X-Request-Id $request_id', $proxyHeaders);
        $this->assertStringContainsString('request.headers.set("X-Request-Id", requestId)', $svelteHooks);
        $this->assertStringContainsString('event.request.headers.get("x-forwarded-for")', $svelteHooks);
        $this->assertStringContainsString('request.headers.set("X-Forwarded-For", forwardedFor)', $svelteHooks);
        $this->assertStringContainsString('$middleware->trustProxies', $applicationBootstrap);
        $this->assertStringContainsString("'172.16.0.0/12'", $applicationBootstrap);
    }

    public function test_gateway_serves_canonical_unsubscribe_pages_from_sveltekit(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));
        $unsubscribePage = base_path('frontend/src/routes/status/[id]/subscribers/unsubscribe/[token]/+page.svelte');
        $unsubscribeAction = base_path('frontend/src/routes/status/[id]/subscribers/unsubscribe/[token]/+page.server.ts');

        $this->assertIsString($gatewayConfiguration);
        $this->assertStringContainsString('location ~* "^/status/[0-9a-hjkmnp-tv-z]{26}/subscribers/unsubscribe/', $gatewayConfiguration);
        $this->assertStringContainsString('^/status/[0-9a-hjkmnp-tv-z]{26}/subscribers/unsubscribe/', $gatewayConfiguration);
        $this->assertStringContainsString('proxy_pass http://sveltekit', $gatewayConfiguration);
        $this->assertFileExists($unsubscribePage);
        $this->assertFileExists($unsubscribeAction);
        $this->assertStringContainsString('subscribers/unsubscribe', (string) file_get_contents($unsubscribeAction));
    }

    public function test_gateway_serves_canonical_subscription_confirmations_from_sveltekit(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));
        $confirmationPage = base_path('frontend/src/routes/status/[id]/subscribers/confirm/[token]/+page.svelte');
        $confirmationAction = base_path('frontend/src/routes/status/[id]/subscribers/confirm/[token]/+page.server.ts');

        $this->assertIsString($gatewayConfiguration);
        $this->assertStringContainsString('location ~* "^/status/[0-9a-hjkmnp-tv-z]{26}/subscribers/confirm/', $gatewayConfiguration);
        $this->assertStringContainsString('if ($request_method !~ "^GET$")', $gatewayConfiguration);
        $this->assertStringContainsString('proxy_pass http://sveltekit', $gatewayConfiguration);
        $this->assertFileExists($confirmationPage);
        $this->assertFileExists($confirmationAction);
        $this->assertStringContainsString('subscribers/confirm', (string) file_get_contents($confirmationAction));
        $this->assertStringContainsString('subscription=confirmed', (string) file_get_contents($confirmationAction));
    }

    public function test_gateway_forwards_canonical_status_page_subscription_posts_to_sveltekit(): void
    {
        $gatewayConfiguration = file_get_contents(base_path('docker/gateway/nginx.conf'));
        $statusPageAction = base_path('frontend/src/routes/status/[id]/+page.server.ts');

        $this->assertIsString($gatewayConfiguration);
        $this->assertStringContainsString('$public_status_cache_control', $gatewayConfiguration);
        $this->assertStringContainsString('^GET$|^HEAD$|^POST$', $gatewayConfiguration);
        $this->assertStringContainsString('proxy_pass http://sveltekit', $gatewayConfiguration);
        $this->assertFileExists($statusPageAction);
        $this->assertStringContainsString('export const actions', (string) file_get_contents($statusPageAction));
        $this->assertStringContainsString('/api/public/status/', (string) file_get_contents($statusPageAction));
        $this->assertStringContainsString('redirect(301', (string) file_get_contents($statusPageAction));
        $this->assertStringContainsString('redirect(307', (string) file_get_contents($statusPageAction));
    }

    public function test_topology_smoke_uses_the_log_mailer_without_changing_the_default_transport(): void
    {
        $smokeScript = file_get_contents(base_path('.github/scripts/smoke-sveltekit-topology.sh'));
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($smokeScript);
        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('export MAIL_MAILER="${MAIL_MAILER:-log}"', $smokeScript);
        $this->assertStringContainsString('MAIL_MAILER: "${MAIL_MAILER:-smtp}"', $composeConfiguration);
    }
}
