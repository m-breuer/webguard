<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class MonitoringContextNavigationTest extends TestCase
{
    public function test_monitoring_pages_share_the_same_context_navigation_with_one_active_tab(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $tabs = [
            __('incidents.analytics.overview.tabs.overview'),
            __('incidents.analytics.overview.tabs.groups'),
            __('incidents.analytics.overview.tabs.status_pages'),
            __('incidents.analytics.overview.tabs.analytics'),
        ];

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
                ->assertSeeTextInOrder($tabs)
                ->assertSeeHtml('aria-current="page"');
        }
    }
}
