<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringResponseTimesPayload;
use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringResponseTimeService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringResponseTimeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_raw_response_times_are_grouped_by_hour_and_aggregated(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $this->createResponse($monitoring, '2026-04-12 10:05:00', 100.0);
        $this->createResponse($monitoring, '2026-04-12 10:15:00', 200.0);
        $this->createResponse($monitoring, '2026-04-12 11:05:00', 300.0);
        $this->createResponse($monitoring, '2026-04-12 11:10:00', null);

        $responseTimes = app(MonitoringResponseTimeService::class)->getResponseTimes(
            $monitoring,
            Date::parse('2026-04-12 00:00:00'),
            Date::parse('2026-04-12 23:59:59')
        );

        $this->assertInstanceOf(MonitoringResponseTimesPayload::class, $responseTimes);
        $this->assertCount(2, $responseTimes->data);
        $this->assertSame('2026-04-12T10:00:00+02:00', $responseTimes->data[0]->date);
        $this->assertEqualsWithDelta(150.0, (float) $responseTimes->data[0]->avg, 0.0001);
        $this->assertEqualsWithDelta(100.0, (float) $responseTimes->data[0]->min, 0.0001);
        $this->assertEqualsWithDelta(200.0, (float) $responseTimes->data[0]->max, 0.0001);
        $this->assertEqualsWithDelta(225.0, (float) $responseTimes->aggregated->avg, 0.0001);
        $this->assertEqualsWithDelta(100.0, (float) $responseTimes->aggregated->min, 0.0001);
        $this->assertEqualsWithDelta(300.0, (float) $responseTimes->aggregated->max, 0.0001);
    }

    public function test_aggregated_response_times_are_loaded_from_daily_results(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $this->createDailyResult($monitoring, '2026-04-10', 100.0, 80, 120);
        $this->createDailyResult($monitoring, '2026-04-11', 300.0, 250, 350);

        $responseTimes = app(MonitoringResponseTimeService::class)->getResponseTimes(
            $monitoring,
            Date::parse('2026-04-10'),
            Date::parse('2026-04-12'),
            true
        );

        $this->assertInstanceOf(MonitoringResponseTimesPayload::class, $responseTimes);
        $this->assertCount(2, $responseTimes->data);
        $this->assertEqualsWithDelta(200.0, (float) $responseTimes->aggregated->avg, 0.0001);
        $this->assertEqualsWithDelta(80.0, (float) $responseTimes->aggregated->min, 0.0001);
        $this->assertEqualsWithDelta(350.0, (float) $responseTimes->aggregated->max, 0.0001);
    }

    private function createResponse(Monitoring $monitoring, string $checkedAt, ?float $responseTime): void
    {
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => $responseTime,
            'created_at' => Date::parse($checkedAt),
            'updated_at' => Date::parse($checkedAt),
        ]);
    }

    private function createDailyResult(Monitoring $monitoring, string $date, float $average, int $minimum, int $maximum): void
    {
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => 1,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 100.0,
            'downtime_percentage' => 0.0,
            'unknown_percentage' => 0.0,
            'uptime_minutes' => 1_440,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => $average,
            'min_response_time' => $minimum,
            'max_response_time' => $maximum,
            'incidents_count' => 0,
        ]);
    }
}
