<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Tests\TestCase;

class MonitoringResponseTimeBackfillMigrationTest extends TestCase
{
    public function test_it_backfills_missing_response_times_for_eligible_monitorings_only(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $eligibleMonitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-17 23:59:59',
        ]);
        $ineligibleMonitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-18 00:00:00',
        ]);

        $this->createDailyResult($eligibleMonitoring, '2026-06-18', 123.4, 100, 150, 10);
        $this->createDailyResult($eligibleMonitoring, '2026-06-19', null, null, null, 99);
        $this->createDailyResult($eligibleMonitoring, '2026-07-09', 234.5, 200, 280, 20);
        $this->createDailyResult($eligibleMonitoring, '2026-07-10', null, null, null, 88);

        $this->createDailyResult($ineligibleMonitoring, '2026-06-18', 321.0, 300, 350, 30);
        $this->createDailyResult($ineligibleMonitoring, '2026-06-19', null, null, null, 77);
        $this->createDailyResult($ineligibleMonitoring, '2026-07-09', 432.0, 400, 480, 40);
        $this->createDailyResult($ineligibleMonitoring, '2026-07-10', null, null, null, 66);

        $this->runMigration();

        $this->assertDailyResult($eligibleMonitoring, '2026-06-19', 123.4, 100, 150, 99);
        $this->assertDailyResult($eligibleMonitoring, '2026-07-10', 234.5, 200, 280, 88);
        $this->assertDailyResult($ineligibleMonitoring, '2026-06-19', null, null, null, 77);
        $this->assertDailyResult($ineligibleMonitoring, '2026-07-10', null, null, null, 66);
    }

    public function test_it_does_not_overwrite_existing_response_times(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-17 23:59:59',
        ]);

        $this->createDailyResult($monitoring, '2026-06-18', 123.4, 100, 150, 10);
        $this->createDailyResult($monitoring, '2026-06-19', 987.6, 900, 999, 99);

        $this->runMigration();

        $this->assertDailyResult($monitoring, '2026-06-19', 987.6, 900, 999, 99);
    }

    public function test_it_creates_missing_daily_results_for_eligible_monitorings_only(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $eligibleMonitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-17 23:59:59',
        ]);
        $ineligibleMonitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-18 00:00:00',
        ]);

        $this->createDailyResult($eligibleMonitoring, '2026-06-18', 123.4, 100, 150, 10);
        $this->createDailyResult($eligibleMonitoring, '2026-07-09', 234.5, 200, 280, 20);
        $this->createDailyResult($ineligibleMonitoring, '2026-06-18', 321.0, 300, 350, 30);

        $this->runMigration();

        $this->assertDailyResult($eligibleMonitoring, '2026-06-19', 123.4, 100, 150, 10);
        $this->assertDailyResult($eligibleMonitoring, '2026-07-10', 234.5, 200, 280, 20);
        $this->assertNull(MonitoringDailyResult::query()
            ->where('monitoring_id', $ineligibleMonitoring->id)
            ->whereDate('date', '2026-06-19')
            ->first());
    }

    private function runMigration(): void
    {
        /** @var Migration $migration */
        $migration = require base_path('database/migrations/2026_08_02_120000_backfill_missing_monitoring_response_times.php');

        $migration->up();
    }

    private function createDailyResult(
        Monitoring $monitoring,
        string $date,
        ?float $averageResponseTime,
        ?int $minimumResponseTime,
        ?int $maximumResponseTime,
        int $uptimeTotal,
    ): void {
        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => $uptimeTotal,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 100.0,
            'downtime_percentage' => 0.0,
            'unknown_percentage' => 0.0,
            'uptime_minutes' => 1_440,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => $averageResponseTime,
            'min_response_time' => $minimumResponseTime,
            'max_response_time' => $maximumResponseTime,
            'incidents_count' => 0,
        ]);
    }

    private function assertDailyResult(
        Monitoring $monitoring,
        string $date,
        ?float $averageResponseTime,
        ?int $minimumResponseTime,
        ?int $maximumResponseTime,
        int $uptimeTotal,
    ): void {
        $dailyResult = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereDate('date', $date)
            ->first();

        $this->assertNotNull($dailyResult);
        $this->assertSame($averageResponseTime, $dailyResult->avg_response_time);
        $this->assertSame($minimumResponseTime, $dailyResult->min_response_time);
        $this->assertSame($maximumResponseTime, $dailyResult->max_response_time);
        $this->assertSame($uptimeTotal, $dailyResult->uptime_total);
    }
}
