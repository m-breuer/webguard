<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDetailCustomRangeCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_detail_page_shows_custom_range_uptime_card(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSee(__('monitoring.detail.custom_range.heading'));
        $testResponse->assertSeeHtml('id="uptime-card-custom-range"');
        $testResponse->assertSeeHtml('id="custom-range-from"');
        $testResponse->assertSeeHtml('id="custom-range-until"');
    }
}
