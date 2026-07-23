<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDetailActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_detail_actions_use_a_labeled_responsive_trigger_and_menu(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-monitoring-actions');
        $testResponse->assertSeeHtml('data-monitoring-actions-trigger');
        $testResponse->assertSeeHtml('aria-controls="monitoring-actions-menu"');
        $testResponse->assertSeeHtml('class="inline-flex min-h-11 w-full items-center justify-between');
        $testResponse->assertSeeHtml('class="absolute end-0 z-30 mt-2 w-full overflow-hidden');
        $testResponse->assertSeeText(__('monitoring.actions.trigger'));
        $testResponse->assertSeeText(__('monitoring.actions.edit'));
        $testResponse->assertSeeText(__('monitoring.actions.reset.heading'));
        $testResponse->assertSeeText(__('monitoring.actions.delete.heading'));
    }
}
