<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use App\Services\InfrastructureHealthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InfrastructureHealthTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_view_infrastructure_health_diagnostics(): void
    {
        Date::setTestNow('2026-05-05 10:00:00');

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Cache::put(
            config('monitoring.infrastructure_health.scheduler_cache_key'),
            Date::now()->subMinute()->toIso8601String(),
            60
        );

        ServerInstance::query()->create([
            'code' => 'diagnostics-1',
            'ip_address' => '192.0.2.80',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(2),
        ]);

        $testResponse = $this->actingAs($admin)->get(route('admin.infrastructure-health.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('admin.infrastructure_health.title'));
        $testResponse->assertSeeText(__('admin.infrastructure_health.checks.database.title'));
        $testResponse->assertSeeText(__('admin.infrastructure_health.checks.cache.title'));
        $testResponse->assertSeeText(__('admin.infrastructure_health.checks.scheduler.title'));
        $testResponse->assertSeeText(__('admin.infrastructure_health.checks.queue.title'));
        $testResponse->assertSeeText(__('admin.infrastructure_health.checks.scanner_instances.title'));
        $testResponse->assertSeeText(__('admin.infrastructure_health.statuses.healthy'));
    }

    public function test_regular_users_cannot_view_infrastructure_health_diagnostics(): void
    {
        Package::factory()->create();
        $user = User::factory()->create(['role' => UserRole::REGULAR]);

        $this->actingAs($user)
            ->get(route('admin.infrastructure-health.index'))
            ->assertForbidden();
    }

    public function test_health_service_reports_degraded_scheduler_queue_and_scanner_state(): void
    {
        Date::setTestNow('2026-05-05 10:00:00');
        config([
            'monitoring.infrastructure_health.scheduler_stale_after_minutes' => 5,
            'monitoring.infrastructure_health.failed_jobs_warning_threshold' => 1,
            'monitoring.instance_stale_after_minutes' => 10,
        ]);

        Cache::put(
            config('monitoring.infrastructure_health.scheduler_cache_key'),
            Date::now()->subMinutes(10)->toIso8601String(),
            60
        );

        DB::table('failed_jobs')->insert([
            'uuid' => 'failed-job-1',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => Date::now(),
        ]);

        ServerInstance::query()->create([
            'code' => 'stale-diagnostics-1',
            'ip_address' => '192.0.2.81',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(20),
        ]);

        $report = app(InfrastructureHealthService::class)->report();

        $this->assertSame(InfrastructureHealthService::STATUS_WARNING, $report['status']);
        $this->assertSame(InfrastructureHealthService::STATUS_WARNING, $report['checks']['scheduler']['status']);
        $this->assertSame(InfrastructureHealthService::STATUS_WARNING, $report['checks']['queue']['status']);
        $this->assertSame(InfrastructureHealthService::STATUS_WARNING, $report['checks']['scanner_instances']['status']);
        $this->assertSame(1, $report['checks']['queue']['meta']['failed_jobs']);
        $this->assertSame(1, $report['checks']['scanner_instances']['meta']['stale_instances']);
    }

    public function test_health_service_reports_invalid_scheduler_heartbeat_without_failing(): void
    {
        Cache::put(
            config('monitoring.infrastructure_health.scheduler_cache_key'),
            'not-a-timestamp',
            60
        );

        $report = app(InfrastructureHealthService::class)->report();

        $this->assertSame(InfrastructureHealthService::STATUS_WARNING, $report['status']);
        $this->assertSame(InfrastructureHealthService::STATUS_WARNING, $report['checks']['scheduler']['status']);
        $this->assertSame(
            'admin.infrastructure_health.checks.scheduler.invalid',
            $report['checks']['scheduler']['message']
        );
    }

    public function test_infrastructure_heartbeat_command_records_scheduler_marker(): void
    {
        Date::setTestNow('2026-05-05 10:00:00');

        $this->artisan('infrastructure:heartbeat')->assertSuccessful();

        $this->assertSame(
            Date::now()->toIso8601String(),
            Cache::get(config('monitoring.infrastructure_health.scheduler_cache_key'))
        );
    }
}
