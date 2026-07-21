<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDetailRecentChecksSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_detail_page_shows_recent_checks_section(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
        ]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.detail.checks.heading'));
        $testResponse->assertDontSeeText(__('monitoring.detail.checks.help'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.no_checks'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.labels.status_code'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.labels.response_time'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.labels.source'));
        $testResponse->assertSeeHtml('id="response-time-range"');
        $testResponse->assertSeeHtml('id="incidents-range"');
        $testResponse->assertSeeHtml('@click="loadMoreChecks()"');
        $testResponse->assertSeeHtml('focus:ring-purple-500');
        $testResponse->assertDontSeeHtml('focus:ring-emerald-500');
        $testResponse->assertDontSeeText(__('monitoring.detail.custom_range.heading'));
        $testResponse->assertDontSeeHtml('id="uptime-card-custom-range"');
        $testResponse->assertSeeHtml('id="recent-checks"');
        $testResponse->assertSeeHtml('data-recent-check-result');
        $testResponse->assertSeeHtml('data-recent-check-status');
    }

    public function test_monitoring_detail_page_uses_tablet_friendly_responsive_layout(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
        ]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3');
        $testResponse->assertSeeHtml('grid grid-cols-12 gap-0.5 sm:flex sm:flex-nowrap');
        $testResponse->assertSeeHtml('grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]');
        $testResponse->assertSeeHtml('data-monitoring-primary-cards class="mb-4 grid grid-cols-1 items-stretch gap-4 md:grid-cols-2 2xl:grid-cols-3"');
        $testResponse->assertSeeHtml('grid grid-cols-1 gap-4 text-center md:grid-cols-2 2xl:grid-cols-3');
        $testResponse->assertSeeHtml('flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between');
        $testResponse->assertSeeHtml('xl:grid-cols-[minmax(13rem,1.25fr)_minmax(0,3fr)_auto] xl:items-center');
    }

    public function test_heartbeat_monitoring_detail_page_keeps_recent_checks_without_response_time_chart(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()
            ->heartbeat()
            ->for($user)
            ->create();

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.detail.heartbeat.heading'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.heading'));
        $testResponse->assertSeeHtml('id="recent-checks"');
        $testResponse->assertDontSeeText(__('monitoring.detail.response_time.heading'));
        $testResponse->assertDontSeeHtml('id="performance-chart"');
    }
}
