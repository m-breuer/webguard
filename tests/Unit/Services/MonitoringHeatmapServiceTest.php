<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringHeatmapService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringHeatmapServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_heatmap_builds_latest_twenty_four_hour_buckets_with_status_minutes(): void
    {
        Date::setTestNow('2026-04-12 12:30:00');

        $monitoring = $this->createMonitoring();

        $this->createResponse($monitoring, MonitoringStatus::UP, '2026-04-12 10:05:00');
        $this->createResponse($monitoring, MonitoringStatus::UP, '2026-04-12 10:15:00');
        $this->createResponse($monitoring, MonitoringStatus::DOWN, '2026-04-12 11:05:00');
        $this->createResponse($monitoring, MonitoringStatus::UNKNOWN, '2026-04-12 12:05:00');

        $heatmap = resolve(MonitoringHeatmapService::class)->getHeatmap(
            $monitoring,
            Date::parse('2026-01-01'),
            Date::parse('2026-01-02')
        );

        $this->assertCount(24, $heatmap);
        $this->assertSame('2026-04-11T13:00:00+02:00', $heatmap->first()['date']->toIso8601String());
        $this->assertSame('2026-04-12T12:00:00+02:00', $heatmap->last()['date']->toIso8601String());

        $byHour = $heatmap->keyBy(fn (array $bucket): string => $bucket['date']->format('Y-m-d H'));

        $this->assertSame(10, $byHour['2026-04-12 10']['uptime']);
        $this->assertSame(0, $byHour['2026-04-12 10']['downtime']);
        $this->assertSame(5, $byHour['2026-04-12 11']['downtime']);
        $this->assertSame(5, $byHour['2026-04-12 12']['unknown']);
    }

    public function test_batched_heatmaps_include_requested_monitorings_and_empty_buckets(): void
    {
        Date::setTestNow('2026-04-12 12:30:00');

        $firstMonitoring = $this->createMonitoring();
        $secondMonitoring = $this->createMonitoring();

        $this->createResponse($firstMonitoring, MonitoringStatus::UP, '2026-04-12 12:05:00');
        $this->createResponse($secondMonitoring, MonitoringStatus::DOWN, '2026-04-12 12:10:00');

        $heatmaps = resolve(MonitoringHeatmapService::class)->getHeatmapsForMonitorings(
            collect([$firstMonitoring, $secondMonitoring]),
            Date::parse('2026-01-01'),
            Date::parse('2026-01-02')
        );

        $this->assertArrayHasKey($firstMonitoring->id, $heatmaps);
        $this->assertArrayHasKey($secondMonitoring->id, $heatmaps);
        $this->assertCount(24, $heatmaps[$firstMonitoring->id]);
        $this->assertCount(24, $heatmaps[$secondMonitoring->id]);
        $this->assertSame(5, $heatmaps[$firstMonitoring->id][23]['uptime']);
        $this->assertSame(5, $heatmaps[$secondMonitoring->id][23]['downtime']);
    }

    private function createMonitoring(): Monitoring
    {
        Package::factory()->create();
        $user = User::factory()->create();

        return Monitoring::factory()->for($user)->create();
    }

    private function createResponse(Monitoring $monitoring, MonitoringStatus $monitoringStatus, string $checkedAt): void
    {
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => $monitoringStatus,
            'http_status_code' => $monitoringStatus === MonitoringStatus::DOWN ? 503 : 200,
            'response_time' => $monitoringStatus === MonitoringStatus::UP ? 120.0 : null,
            'created_at' => Date::parse($checkedAt),
            'updated_at' => Date::parse($checkedAt),
        ]);
    }
}
