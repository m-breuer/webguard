<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\MonitoringDashboardPayload;
use App\Data\MonitoringSslPayload;
use App\Data\MonitoringUptimeCalendarPayload;
use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringDashboardPayloadService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringDashboardPayloadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_payload_returns_all_monitoring_api_sections_without_controller_responses(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring([
            'created_at' => Date::parse('2026-04-10 00:00:00'),
        ]);

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 150.0,
            'created_at' => Date::parse('2026-04-12 11:00:00'),
            'updated_at' => Date::parse('2026-04-12 11:00:00'),
        ]);

        $monitoringDashboardPayload = resolve(MonitoringDashboardPayloadService::class)->getPayload(
            $monitoring->fresh(),
            1,
            Date::parse('2026-04-10'),
            Date::parse('2026-04-12')
        );
        $payload = $monitoringDashboardPayload->toArray();

        $this->assertInstanceOf(MonitoringDashboardPayload::class, $monitoringDashboardPayload);
        $this->assertInstanceOf(MonitoringSslPayload::class, $monitoringDashboardPayload->ssl);
        $this->assertInstanceOf(MonitoringUptimeCalendarPayload::class, $monitoringDashboardPayload->uptimeCalendar);
        $this->assertSame([
            'status_since',
            'status_now',
            'uptime_downtime',
            'response_times',
            'incidents',
            'heatmap',
            'ssl',
            'uptime_calendar',
        ], array_keys($payload));
        $this->assertSame(MonitoringStatus::UP->value, $payload['status_since']['status']);
        $this->assertArrayHasKey('uptime', $payload['uptime_downtime']);
        $this->assertArrayHasKey('aggregated', $payload['response_times']);
        $this->assertCount(24, $payload['heatmap']);
        $this->assertSame(
            100.0,
            $payload['uptime_calendar']['2026-04']['days'][11]['uptime_percentage']
        );
    }

    private function createMonitoring(array $attributes = []): Monitoring
    {
        Package::factory()->create();
        $user = User::factory()->create();

        return Monitoring::factory()->for($user)->create($attributes);
    }
}
