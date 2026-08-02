<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Tests\TestCase;

class MonitoringDailyResultAggregateBackfillMigrationTest extends TestCase
{
    public function test_it_backfills_empty_daily_aggregates_for_eligible_monitorings_only(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $eligibleMonitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-17 23:59:59',
        ]);
        $ineligibleMonitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-18 00:00:00',
        ]);

        $this->createDailyResult($eligibleMonitoring, '2026-06-18', [
            'uptime_total' => 100,
            'downtime_total' => 2,
            'unknown_total' => 1,
            'uptime_percentage' => 90.0,
            'downtime_percentage' => 9.0,
            'unknown_percentage' => 1.0,
            'uptime_minutes' => 1_296,
            'downtime_minutes' => 130,
            'unknown_minutes' => 14,
            'avg_response_time' => 123.4,
            'min_response_time' => 100,
            'max_response_time' => 150,
            'incidents_count' => 2,
        ]);
        $this->createDailyResult($eligibleMonitoring, '2026-06-19');
        $this->createDailyResult($eligibleMonitoring, '2026-07-09', [
            'uptime_total' => 200,
            'downtime_total' => 4,
            'unknown_total' => 3,
            'uptime_percentage' => 80.0,
            'downtime_percentage' => 18.0,
            'unknown_percentage' => 2.0,
            'uptime_minutes' => 1_152,
            'downtime_minutes' => 259,
            'unknown_minutes' => 29,
            'avg_response_time' => 234.5,
            'min_response_time' => 200,
            'max_response_time' => 280,
            'incidents_count' => 4,
        ]);
        $this->createDailyResult($eligibleMonitoring, '2026-07-10');

        $this->createDailyResult($ineligibleMonitoring, '2026-06-18', [
            'uptime_minutes' => 1_440,
            'avg_response_time' => 321.0,
        ]);
        $this->createDailyResult($ineligibleMonitoring, '2026-06-19');

        $this->runMigration();

        $this->assertDailyResultMatchesSource($eligibleMonitoring, '2026-06-19', [
            'uptime_total' => 100,
            'downtime_total' => 2,
            'unknown_total' => 1,
            'uptime_minutes' => 1_296,
            'downtime_minutes' => 130,
            'unknown_minutes' => 14,
            'avg_response_time' => 123.4,
            'min_response_time' => 100,
            'max_response_time' => 150,
            'incidents_count' => 2,
        ]);
        $this->assertDailyResultMatchesSource($eligibleMonitoring, '2026-07-10', [
            'uptime_total' => 200,
            'downtime_total' => 4,
            'unknown_total' => 3,
            'uptime_minutes' => 1_152,
            'downtime_minutes' => 259,
            'unknown_minutes' => 29,
            'avg_response_time' => 234.5,
            'min_response_time' => 200,
            'max_response_time' => 280,
            'incidents_count' => 4,
        ]);
        $this->assertDailyResultMatchesSource($ineligibleMonitoring, '2026-06-19', [
            'uptime_total' => 0,
            'uptime_minutes' => 0,
            'downtime_minutes' => 0,
            'avg_response_time' => null,
        ]);
    }

    public function test_it_does_not_overwrite_daily_aggregates_with_tracked_minutes(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'created_at' => '2026-06-17 23:59:59',
        ]);

        $this->createDailyResult($monitoring, '2026-06-18', [
            'uptime_minutes' => 1_296,
            'downtime_minutes' => 130,
            'avg_response_time' => 123.4,
        ]);
        $this->createDailyResult($monitoring, '2026-06-19', [
            'uptime_total' => 1,
            'uptime_minutes' => 1_440,
            'avg_response_time' => 987.6,
        ]);

        $this->runMigration();

        $this->assertDailyResultMatchesSource($monitoring, '2026-06-19', [
            'uptime_total' => 1,
            'uptime_minutes' => 1_440,
            'downtime_minutes' => 0,
            'avg_response_time' => 987.6,
        ]);
    }

    private function runMigration(): void
    {
        /** @var Migration $migration */
        $migration = require base_path('database/migrations/2026_08_02_130000_backfill_empty_monitoring_daily_results.php');

        $migration->up();
    }

    /**
     * @param  array<string, float|int|null>  $attributes
     */
    private function createDailyResult(Monitoring $monitoring, string $date, array $attributes = []): void
    {
        MonitoringDailyResult::query()->create(array_merge([
            'monitoring_id' => $monitoring->id,
            'date' => $date,
            'uptime_total' => 0,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 0.0,
            'downtime_percentage' => 0.0,
            'unknown_percentage' => 0.0,
            'uptime_minutes' => 0,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => null,
            'min_response_time' => null,
            'max_response_time' => null,
            'incidents_count' => 0,
        ], $attributes));
    }

    /**
     * @param  array<string, float|int|null>  $expected
     */
    private function assertDailyResultMatchesSource(Monitoring $monitoring, string $date, array $expected): void
    {
        $dailyResult = MonitoringDailyResult::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereDate('date', $date)
            ->first();

        $this->assertNotNull($dailyResult);

        foreach ($expected as $column => $value) {
            $this->assertSame($value, $dailyResult->{$column});
        }
    }
}
