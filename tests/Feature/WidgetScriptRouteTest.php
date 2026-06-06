<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class WidgetScriptRouteTest extends TestCase
{
    public function test_widget_script_uses_public_widget_endpoint_without_hard_coded_environment_domain(): void
    {
        $testResponse = $this->get(route('widget.js'));

        $testResponse->assertOk();
        $this->assertStringContainsString('/api/public/monitorings/', $testResponse->getContent());
        $this->assertStringContainsString('const escapeHtml =', $testResponse->getContent());
        $this->assertStringContainsString('${escapeHtml(monitoringName)}', $testResponse->getContent());
        $this->assertStringNotContainsString('${data.name}', $testResponse->getContent());
        $this->assertStringNotContainsString('webguard.m-breuer.dev', $testResponse->getContent());
    }

    public function test_sla_badge_script_uses_public_widget_endpoint_without_hard_coded_environment_domain(): void
    {
        $testResponse = $this->get(route('badge.js'));

        $testResponse->assertOk();
        $this->assertStringContainsString('[data-webguard-sla-badge]', $testResponse->getContent());
        $this->assertStringContainsString('/api/public/monitorings/', $testResponse->getContent());
        $this->assertStringContainsString('Verified by WebGuard', $testResponse->getContent());
        $this->assertStringNotContainsString('webguard.m-breuer.dev', $testResponse->getContent());
    }
}
