<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusPageComponentSource;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class PublicStatusPageCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_public_status_page_calendar_aggregates_unique_monitorings_for_the_last_30_days(): void
    {
        Date::setTestNow('2026-07-16 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $firstMonitoring = Monitoring::factory()->for($user)->create();
        $secondMonitoring = Monitoring::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Customer Status',
            'slug' => 'customer-status',
            'is_public' => true,
        ]);

        $statusPageComponent = $statusPage->components()->create([
            'name' => 'API',
            'position' => 0,
            'source_type' => StatusPageComponentSource::MANUAL,
        ]);
        $statusPageComponent->monitorings()->attach($firstMonitoring->id, ['position' => 0]);

        $monitoringGroup = MonitoringGroup::factory()->for($user)->create();
        $monitoringGroup->monitorings()->attach([$firstMonitoring->id, $secondMonitoring->id]);
        $statusPage->components()->create([
            'name' => 'Infrastructure',
            'position' => 1,
            'source_type' => StatusPageComponentSource::MONITORING_GROUP,
            'monitoring_group_id' => $monitoringGroup->id,
        ]);

        $this->createDailyResult($firstMonitoring, '2026-07-15', 60, 120);
        $this->createDailyResult($secondMonitoring, '2026-07-15', 180, 60);
        $dailyResultCount = MonitoringDailyResult::query()->count();

        $testResponse = $this->getJson(route('public.status-pages.uptime-calendar', $statusPage));

        $testResponse->assertOk();
        $calendar = $testResponse->json();

        $this->assertArrayHasKey('2026-06', $calendar);
        $this->assertArrayHasKey('2026-07', $calendar);
        $this->assertSame(30, $this->countDaysInRange($calendar));
        $this->assertEqualsWithDelta(
            57.142857,
            $calendar['2026-07']['days'][14]['uptime_percentage'],
            0.0001
        );
        $this->assertEqualsWithDelta(57.142857, $calendar['2026-07']['monthly_average_uptime'], 0.0001);
        $this->assertSame($dailyResultCount, MonitoringDailyResult::query()->count());
    }

    public function test_public_status_page_calendar_is_not_available_for_private_status_pages(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Private Status',
            'slug' => 'private-status',
            'is_public' => false,
        ]);

        $this->getJson(route('public.status-pages.uptime-calendar', $statusPage))
            ->assertNotFound();
    }

    public function test_public_status_page_renders_aggregate_calendar_for_multiple_monitorings(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $firstMonitoring = Monitoring::factory()->for($user)->create();
        $secondMonitoring = Monitoring::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Customer Status',
            'slug' => 'customer-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create([
            'name' => 'Services',
            'position' => 0,
            'source_type' => StatusPageComponentSource::MANUAL,
        ]);
        $statusPageComponent->monitorings()->attach([
            $firstMonitoring->id => ['position' => 0],
            $secondMonitoring->id => ['position' => 1],
        ]);

        $this->get(route('public-status-pages.show', $statusPage))
            ->assertOk()
            ->assertSeeText(__('status_page.public.calendar.heading'))
            ->assertSeeHtml('status-page-uptime-calendar');
    }

    /**
     * @param  array<string, array{days: list<array{date: string, uptime_percentage: float|null}>}>  $calendar
     */
    private function countDaysInRange(array $calendar): int
    {
        $startDate = Date::now()->subDays(29)->startOfDay();
        $endDate = Date::now()->endOfDay();

        return collect($calendar)
            ->flatMap(fn (array $month): array => $month['days'])
            ->filter(function (array $day) use ($startDate, $endDate): bool {
                $date = Date::parse($day['date']);

                return $date->between($startDate, $endDate);
            })
            ->count();
    }

    private function createDailyResult(
        Monitoring $monitoring,
        string $date,
        int $uptimeMinutes,
        int $downtimeMinutes
    ): void {
        $trackedMinutes = $uptimeMinutes + $downtimeMinutes;

        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => $uptimeMinutes,
            'downtime_total' => $downtimeMinutes,
            'unknown_total' => 0,
            'uptime_percentage' => ($uptimeMinutes / $trackedMinutes) * 100,
            'downtime_percentage' => ($downtimeMinutes / $trackedMinutes) * 100,
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
