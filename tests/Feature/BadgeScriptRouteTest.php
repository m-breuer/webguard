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
        $this->assertStringContainsString('public', (string) $testResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=300', (string) $testResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('stale-while-revalidate=60', (string) $testResponse->headers->get('Cache-Control'));
        $testResponse->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $testResponse->assertHeader('Cross-Origin-Resource-Policy', 'cross-origin');
        $testResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('[data-webguard-sla-badge]', $testResponse->getContent());
        $this->assertStringContainsString('/api/public/monitorings/${encodeURIComponent(monitoringId)}/badge', $testResponse->getContent());
        $this->assertStringContainsString('Verified by WebGuard', $testResponse->getContent());
        $this->assertStringNotContainsString('webguard.m-breuer.dev', $testResponse->getContent());
    }
}
