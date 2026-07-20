<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class MonitoringContextNavigationTest extends TestCase
{
    public function test_monitoring_pages_share_the_same_context_header_without_redundant_tab_navigation(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        foreach ([
            'monitorings.index',
            'monitoring-groups.index',
            'status-pages.index',
            'incidents.analytics',
        ] as $routeName) {
            $testResponse = $this->actingAs($user)->get(route($routeName));

            $testResponse->assertOk()
                ->assertSeeHtml('data-monitoring-context')
                ->assertSeeText(__('incidents.analytics.title'))
                ->assertSeeText(__('incidents.analytics.description'))
                ->assertDontSeeHtml('aria-label="' . __('incidents.analytics.title') . '"');
        }
    }
}
