<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDetailContextLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_detail_page_exposes_context_first_layout(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'WebGuard API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://api.example.test/health',
            'notification_channels' => ['discord', 'mobile_push'],
        ]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('data-monitoring-detail-header');
        $testResponse->assertSeeHtml('data-monitoring-summary');
        $testResponse->assertSeeHtml('data-monitoring-detail-layout');
        $testResponse->assertSeeHtml('data-monitoring-context-rail');
        $testResponse->assertSeeText(__('monitoring.detail.summary.current_status'));
        $testResponse->assertSeeText(__('monitoring.detail.summary.last_check'));
        $testResponse->assertSeeText(__('monitoring.detail.context.ownership'));
        $testResponse->assertSeeText(__('monitoring.detail.context.notifications'));
        $testResponse->assertSeeText(__('notifications.channels.discord'));
        $testResponse->assertSeeText(__('monitoring.detail.context.no_status_pages'));
        $testResponse->assertSeeText($monitoring->name);
        $testResponse->assertSeeText($monitoring->target);
    }
}
