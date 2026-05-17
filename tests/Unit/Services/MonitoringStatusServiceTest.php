<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringStatusService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringStatusServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_status_since_uses_latest_incident_when_present(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring();

        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::parse('2026-04-12 10:00:00'),
            'up_at' => null,
        ]);

        $statusSince = resolve(MonitoringStatusService::class)->getStatusSince($monitoring->fresh());

        $this->assertSame(MonitoringStatus::DOWN->value, $statusSince['status']);
        $this->assertSame('2026-04-12T10:00:00+02:00', $statusSince['since']);
    }

    public function test_status_since_falls_back_to_latest_response_or_unknown(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring([
            'created_at' => Date::parse('2026-04-12 09:00:00'),
        ]);

        $unknownStatus = resolve(MonitoringStatusService::class)->getStatusSince($monitoring->fresh());

        $this->assertSame(MonitoringStatus::UNKNOWN->value, $unknownStatus['status']);
        $this->assertNull($unknownStatus['since']);

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 120.0,
            'created_at' => Date::parse('2026-04-12 11:00:00'),
            'updated_at' => Date::parse('2026-04-12 11:00:00'),
        ]);

        $upStatus = resolve(MonitoringStatusService::class)->getStatusSince($monitoring->fresh());

        $this->assertSame(MonitoringStatus::UP->value, $upStatus['status']);
        $this->assertSame('2026-04-12T09:00:00+02:00', $upStatus['since']);
    }

    public function test_status_now_calculates_next_check_for_regular_and_heartbeat_monitorings(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        $monitoring = $this->createMonitoring();

        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 120.0,
            'created_at' => Date::parse('2026-04-12 11:55:00'),
            'updated_at' => Date::parse('2026-04-12 11:55:00'),
        ]);

        $statusNow = resolve(MonitoringStatusService::class)->getStatusNow($monitoring->fresh(), 120);

        $this->assertSame(MonitoringStatus::UP, $statusNow['status']);
        $this->assertSame('2026-04-12T11:55:00+02:00', $statusNow['checked_at']);
        $this->assertSame('2026-04-12T11:57:00+02:00', $statusNow['next']);
        $this->assertSame(120, $statusNow['interval']);

        $heartbeat = $this->createMonitoring([
            'created_at' => Date::parse('2026-04-12 08:00:00'),
        ], heartbeat: true);
        $heartbeat->forceFill([
            'heartbeat_interval_minutes' => 15,
            'heartbeat_last_ping_at' => Date::parse('2026-04-12 11:50:00'),
        ])->save();

        $heartbeatStatus = resolve(MonitoringStatusService::class)->getStatusNow($heartbeat->fresh());

        $this->assertSame(MonitoringStatus::UNKNOWN->value, $heartbeatStatus['status']);
        $this->assertSame('2026-04-12T11:50:00+02:00', $heartbeatStatus['checked_at']);
        $this->assertSame('2026-04-12T12:05:00+02:00', $heartbeatStatus['next']);
        $this->assertSame(900, $heartbeatStatus['interval']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMonitoring(array $attributes = [], bool $heartbeat = false): Monitoring
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $factory = Monitoring::factory()->for($user);

        if ($heartbeat) {
            $factory = $factory->heartbeat();
        }

        return $factory->create($attributes);
    }
}
