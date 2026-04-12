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
        $this->assertStringNotContainsString('webguard.m-breuer.dev', $testResponse->getContent());
    }
}
