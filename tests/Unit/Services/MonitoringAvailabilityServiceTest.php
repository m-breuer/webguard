<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringAvailabilityPayload;
use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringAvailabilityService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringAvailabilityServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_raw_availability_calculates_status_durations_and_overlapping_incidents(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::parse('2026-04-12 08:00:00'),
        ]);

        $this->createResponse($monitoring, MonitoringStatus::UP, '2026-04-12 08:00:00');
        $this->createResponse($monitoring, MonitoringStatus::DOWN, '2026-04-12 09:00:00');
        $this->createResponse($monitoring, MonitoringStatus::UP, '2026-04-12 10:30:00');

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::parse('2026-04-12 09:00:00'),
            'up_at' => Date::parse('2026-04-12 10:30:00'),
        ]);

        $stats = app(MonitoringAvailabilityService::class)->getUptimeDowntime(
            $monitoring,
            Date::parse('2026-04-12 08:00:00'),
            Date::parse('2026-04-12 11:00:00')
        );

        $this->assertInstanceOf(MonitoringAvailabilityPayload::class, $stats);
        $this->assertTrue($stats->hasData);
        $this->assertSame(90, $stats->uptime->minutes);
        $this->assertSame(90, $stats->downtime->minutes);
        $this->assertSame(0, $stats->unknown->minutes);
        $this->assertSame(1, $stats->downtime->incidentsCount);
        $this->assertEqualsWithDelta(50.0, (float) $stats->uptime->percentage, 0.0001);
        $this->assertEqualsWithDelta(50.0, (float) $stats->downtime->percentage, 0.0001);
    }

    public function test_multi_range_summary_uses_daily_aggregates_in_one_pass(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::now()->subDays(30),
        ]);

        foreach (range(1, 10) as $daysAgo) {
            $this->createDailyResult($monitoring, Date::now()->subDays($daysAgo)->toDateString());
        }

        $statsByRange = app(MonitoringAvailabilityService::class)->getUptimeDowntimesForRanges($monitoring, [7, 10]);

        $this->assertInstanceOf(MonitoringAvailabilityPayload::class, $statsByRange['7']);
        $this->assertInstanceOf(MonitoringAvailabilityPayload::class, $statsByRange['10']);
        $this->assertSame(700, $statsByRange['7']->uptime->minutes);
        $this->assertSame(70, $statsByRange['7']->downtime->minutes);
        $this->assertSame(7, $statsByRange['7']->downtime->incidentsCount);
        $this->assertSame(1_000, $statsByRange['10']->uptime->minutes);
        $this->assertSame(100, $statsByRange['10']->downtime->minutes);
        $this->assertSame(10, $statsByRange['10']->downtime->incidentsCount);
    }

    private function createResponse(Monitoring $monitoring, MonitoringStatus $status, string $checkedAt): void
    {
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => $status,
            'http_status_code' => $status === MonitoringStatus::UP ? 200 : 503,
            'response_time' => 100.0,
            'created_at' => Date::parse($checkedAt),
            'updated_at' => Date::parse($checkedAt),
        ]);
    }

    private function createDailyResult(Monitoring $monitoring, string $date): void
    {
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => 10,
            'downtime_total' => 1,
            'unknown_total' => 0,
            'uptime_percentage' => 90.0,
            'downtime_percentage' => 10.0,
            'unknown_percentage' => 0.0,
            'uptime_minutes' => 100,
            'downtime_minutes' => 10,
            'unknown_minutes' => 0,
            'avg_response_time' => 100.0,
            'min_response_time' => 80,
            'max_response_time' => 120,
            'incidents_count' => 1,
        ]);
    }
}
