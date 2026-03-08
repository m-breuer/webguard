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

        $response = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $response->assertOk();
        $response->assertSee(__('monitoring.detail.custom_range.heading'));
        $response->assertSee('id="uptime-card-custom-range"', false);
        $response->assertSee('id="custom-range-from"', false);
        $response->assertSee('id="custom-range-until"', false);
    }
}
