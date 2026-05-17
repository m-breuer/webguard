<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringUptimeCalendarPayload;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringUptimeCalendarService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringUptimeCalendarServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_calendar_groups_days_by_month_and_calculates_monthly_average_from_minutes(): void
    {
        Date::setTestNow('2026-04-20 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => Date::parse('2026-04-01 00:00:00'),
        ]);

        $this->createDailyResult($monitoring, '2026-04-10', 75.0, 90, 30);
        $this->createDailyResult($monitoring, '2026-04-11', 50.0, 60, 60);

        $calendarPayload = app(MonitoringUptimeCalendarService::class)->getGroupedByDateAndMonth(
            $monitoring,
            Date::parse('2026-04-01')->startOfDay(),
            Date::parse('2026-04-30')->endOfDay()
        );
        $calendar = $calendarPayload->toArray();

        $this->assertInstanceOf(MonitoringUptimeCalendarPayload::class, $calendarPayload);
        $this->assertArrayHasKey('2026-04', $calendar);
        $this->assertCount(30, $calendar['2026-04']['days']);
        $this->assertEqualsWithDelta(62.5, (float) $calendar['2026-04']['monthly_average_uptime'], 0.0001);
        $this->assertSame(75.0, $calendar['2026-04']['days'][9]['uptime_percentage']);
        $this->assertNull($calendar['2026-04']['days'][0]['uptime_percentage']);
    }

    private function createDailyResult(
        Monitoring $monitoring,
        string $date,
        float $uptimePercentage,
        int $uptimeMinutes,
        int $downtimeMinutes
    ): void {
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => 1,
            'downtime_total' => 1,
            'unknown_total' => 0,
            'uptime_percentage' => $uptimePercentage,
            'downtime_percentage' => 100 - $uptimePercentage,
            'unknown_percentage' => 0.0,
            'uptime_minutes' => $uptimeMinutes,
            'downtime_minutes' => $downtimeMinutes,
            'unknown_minutes' => 0,
            'avg_response_time' => 100.0,
            'min_response_time' => 100,
            'max_response_time' => 100,
            'incidents_count' => 0,
        ]);
    }
}
