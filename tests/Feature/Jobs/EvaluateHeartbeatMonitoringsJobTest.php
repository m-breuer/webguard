<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Jobs\EvaluateHeartbeatMonitoringsJob;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class EvaluateHeartbeatMonitoringsJobTest extends TestCase
{
    public function test_it_marks_overdue_heartbeat_monitorings_as_down_only_once(): void
    {
        Date::setTestNow('2026-04-18 12:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);

        $monitoring = Monitoring::factory()
            ->heartbeat()
            ->for($user)
            ->create([
                'preferred_location' => $serverInstance->code,
                'status' => MonitoringLifecycleStatus::ACTIVE,
                'heartbeat_token' => 'heartbeat-token',
                'target' => route('monitorings.heartbeat.ping', ['token' => 'heartbeat-token']),
                'heartbeat_interval_minutes' => 30,
                'heartbeat_grace_minutes' => 5,
                'heartbeat_last_ping_at' => Date::now()->subHours(2),
            ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => null,
            'created_at' => Date::now()->subHours(2),
            'updated_at' => Date::now()->subHours(2),
        ]);

        (new EvaluateHeartbeatMonitoringsJob)->handle();
        (new EvaluateHeartbeatMonitoringsJob)->handle();

        $this->assertSame(1, MonitoringResponse::query()
            ->where('monitoring_id', $monitoring->id)
            ->where('status', MonitoringStatus::DOWN)
            ->count());
        $this->assertSame(1, Incident::query()
            ->where('monitoring_id', $monitoring->id)
            ->count());
    }
}
