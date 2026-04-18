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
        $testResponse->assertSeeText(__('monitoring.detail.checks.help'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.no_checks'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.labels.status_code'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.labels.response_time'));
        $testResponse->assertSeeText(__('monitoring.detail.checks.labels.source'));
        $testResponse->assertSeeHtml('id="response-time-range"');
        $testResponse->assertSeeHtml('id="incidents-range"');
        $testResponse->assertSeeHtml('@click="loadMoreChecks()"');
        $testResponse->assertDontSeeText(__('monitoring.detail.custom_range.heading'));
        $testResponse->assertDontSeeHtml('id="uptime-card-custom-range"');
        $testResponse->assertSeeHtml('id="recent-checks"');
    }
}
