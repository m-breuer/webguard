<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class BadgeScriptRouteTest extends TestCase
{
    public function test_sla_badge_script_uses_public_badge_endpoint_without_hard_coded_environment_domain(): void
    {
        $testResponse = $this->get(route('badge.js'));

        $testResponse->assertOk();
        $this->assertStringContainsString('[data-webguard-sla-badge]', $testResponse->getContent());
        $this->assertStringContainsString('/api/public/monitorings/${encodeURIComponent(monitoringId)}/badge', $testResponse->getContent());
        $this->assertStringContainsString('Verified by WebGuard', $testResponse->getContent());
        $this->assertStringNotContainsString('webguard.m-breuer.dev', $testResponse->getContent());
    }
}
