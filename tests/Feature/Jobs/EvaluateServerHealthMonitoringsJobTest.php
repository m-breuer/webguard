<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\MonitoringLifecycleStatus;
use App\Jobs\EvaluateServerHealthMonitoringsJob;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluateServerHealthMonitoringsJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_it_records_one_stale_report_after_the_expected_interval_and_grace_period(): void
    {
        Date::setTestNow('2026-08-08 12:00:00');

        $monitoring = $this->createServerHealthMonitoring([
            'server_health_last_reported_at' => Date::now()->subMinutes(7),
            'server_health_report_interval_minutes' => 1,
            'server_health_grace_minutes' => 5,
            'failure_confirmation_threshold' => 1,
        ]);

        (new EvaluateServerHealthMonitoringsJob)->handle();
        (new EvaluateServerHealthMonitoringsJob)->handle();

        $this->assertSame(1, MonitoringResponse::query()
            ->where('monitoring_id', $monitoring->id)
            ->whereJsonContains('vital_values->server_health_report_stale', true)
            ->count());
        $this->assertDatabaseHas('incidents', ['monitoring_id' => $monitoring->id, 'up_at' => null]);
    }

    public function test_it_skips_reports_that_are_still_within_the_grace_period_or_under_maintenance(): void
    {
        Date::setTestNow('2026-08-08 12:00:00');

        $withinGrace = $this->createServerHealthMonitoring([
            'server_health_last_reported_at' => Date::now()->subMinutes(5),
            'server_health_report_interval_minutes' => 1,
            'server_health_grace_minutes' => 5,
        ]);
        $underMaintenance = $this->createServerHealthMonitoring([
            'server_health_last_reported_at' => Date::now()->subHour(),
            'maintenance_from' => Date::now()->subMinute(),
            'maintenance_until' => Date::now()->addMinute(),
        ]);

        (new EvaluateServerHealthMonitoringsJob)->handle();

        $this->assertSame(0, MonitoringResponse::query()
            ->whereIn('monitoring_id', [$withinGrace->id, $underMaintenance->id])
            ->whereJsonContains('vital_values->server_health_report_stale', true)
            ->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createServerHealthMonitoring(array $overrides = []): Monitoring
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true],
        );

        $token = (string) Str::ulid();

        return Monitoring::factory()->serverHealth()->for($user)->create(array_merge([
            'preferred_location' => $serverInstance->code,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'server_health_token' => $token,
            'target' => route('v1.server-health.store', ['token' => $token]),
            'server_health_report_interval_minutes' => 1,
            'server_health_grace_minutes' => 5,
        ], $overrides));
    }
}
