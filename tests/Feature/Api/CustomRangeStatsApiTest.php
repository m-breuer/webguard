<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class CustomRangeStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_custom_range_uptime_percentage_and_incident_count(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subHours(6),
        ]);

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::today()->addHours(1),
            'up_at' => Date::today()->addHours(2),
        ]);

        $url = '/api/v1/monitorings/' . $monitoring->id
            . '/custom-range-stats?from=' . Date::today()->toDateString()
            . '&until=' . Date::today()->toDateString();

        $testResponse = $this->actingAs($user)->getJson($url);

        $testResponse->assertOk();
        $testResponse->assertJsonPath('incidents_count', 1);
        $testResponse->assertJsonPath('from', Date::today()->toDateString());
        $testResponse->assertJsonPath('until', Date::today()->toDateString());

        $expectedUptimePercentage = ((24 * 60 - 60) / (24 * 60)) * 100;
        $this->assertEqualsWithDelta($expectedUptimePercentage, (float) $testResponse->json('uptime_percentage'), 0.0001);
    }

    public function test_validates_custom_range_date_order(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $testResponse = $this->actingAs($user)->getJson(
            '/api/v1/monitorings/' . $monitoring->id . '/custom-range-stats?from=2026-02-10&until=2026-02-09'
        );

        $testResponse->assertUnprocessable();
        $testResponse->assertJsonValidationErrors(['until']);
    }
}
