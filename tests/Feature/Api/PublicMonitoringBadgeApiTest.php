<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringResponse;
use App\Models\MonitoringSslResult;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicMonitoringBadgeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_badge_endpoint_returns_public_monitoring_payload_without_authentication(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subDays(10),
        ]);

        $checkedAt = Date::now()->subMinutes(5);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'created_at' => $checkedAt,
            'updated_at' => $checkedAt,
        ]);

        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => Date::now()->subDays(2)->toDateString(),
            'uptime_total' => 1,
            'downtime_total' => 0,
            'unknown_total' => 0,
            'uptime_percentage' => 100,
            'downtime_percentage' => 0,
            'unknown_percentage' => 0,
            'uptime_minutes' => 24 * 60,
            'downtime_minutes' => 0,
            'unknown_minutes' => 0,
            'avg_response_time' => 123.4,
            'min_response_time' => 123.4,
            'max_response_time' => 123.4,
            'incidents_count' => 0,
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDays(20),
            'up_at' => Date::now()->subDays(20)->addMinutes(10),
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDays(80),
            'up_at' => Date::now()->subDays(80)->addMinutes(10),
        ]);
        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'is_valid' => true,
            'issuer' => 'Example CA',
            'issued_at' => Date::now()->subDays(30),
            'expires_at' => Date::parse('2026-08-01 00:00:00'),
        ]);
        MonitoringDomainResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'is_valid' => true,
            'registrar' => 'Example Registrar',
            'checked_at' => Date::now(),
            'expires_at' => Date::parse('2027-02-01 00:00:00'),
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('name', 'Primary API');
        $testResponse->assertJsonPath('status', MonitoringStatus::UP->value);
        $testResponse->assertJsonPath('status_label', 'UP');
        $testResponse->assertJsonPath('status_code', 200);
        $testResponse->assertJsonPath('status_identifier', 'status.success');
        $testResponse->assertJsonPath('public_url', route('public-label', $monitoring));
        $testResponse->assertJsonPath('incidents.30_days', 1);
        $testResponse->assertJsonPath('incidents.90_days', 2);
        $testResponse->assertJsonPath('incidents.365_days', 2);
        $testResponse->assertJsonPath('ssl.valid', true);
        $testResponse->assertJsonPath('domain.valid', true);
        $testResponse->assertJsonPath('maintenance.active', false);
        $this->assertIsNumeric($testResponse->json('uptime.7_days'));
        $this->assertIsNumeric($testResponse->json('uptime.30_days'));
        $this->assertIsNumeric($testResponse->json('uptime.90_days'));
        $this->assertIsNumeric($testResponse->json('uptime.365_days'));
    }

    public function test_public_badge_endpoint_batches_uptime_range_queries(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subDays(400),
        ]);

        foreach ([7, 30, 90, 365] as $days) {
            MonitoringDailyResult::query()->create([
                'monitoring_id' => $monitoring->id,
                'date' => Date::now()->subDays($days)->toDateString(),
                'uptime_total' => 1,
                'downtime_total' => 0,
                'unknown_total' => 0,
                'uptime_percentage' => 100,
                'downtime_percentage' => 0,
                'unknown_percentage' => 0,
                'uptime_minutes' => 24 * 60,
                'downtime_minutes' => 0,
                'unknown_minutes' => 0,
                'avg_response_time' => 123.4,
                'min_response_time' => 123.4,
                'max_response_time' => 123.4,
                'incidents_count' => 0,
            ]);
        }

        $checkedAt = Date::now()->subMinutes(5);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'created_at' => $checkedAt,
            'updated_at' => $checkedAt,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('uptime.7_days', 100);
        $testResponse->assertJsonPath('uptime.30_days', 100);
        $testResponse->assertJsonPath('uptime.90_days', 100);
        $testResponse->assertJsonPath('uptime.365_days', 100);

        $selectCount = collect(DB::getQueryLog())
            ->filter(static fn (array $entry): bool => str_starts_with(mb_strtolower($entry['query']), 'select'))
            ->count();

        $this->assertLessThanOrEqual(8, $selectCount, (string) collect(DB::getQueryLog())->pluck('query')->implode(PHP_EOL));
    }

    public function test_public_badge_endpoint_uses_live_uptime_for_fresh_monitoring_without_daily_results(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Fresh API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::parse('2026-04-12 10:00:00'),
        ]);

        $checkedAt = Date::parse('2026-04-12 11:00:00');
        $monitoringResponse = MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
        ]);

        DB::table('monitoring_response_results')->where('id', $monitoringResponse->id)->update([
            'created_at' => $checkedAt,
            'updated_at' => $checkedAt,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('status', MonitoringStatus::UP->value);
        $testResponse->assertJsonPath('uptime.7_days', 100);
        $testResponse->assertJsonPath('uptime.30_days', 100);
        $testResponse->assertJsonPath('uptime.90_days', 100);
        $testResponse->assertJsonPath('uptime.365_days', 100);

        $selectCount = collect(DB::getQueryLog())
            ->filter(static fn (array $entry): bool => str_starts_with(mb_strtolower($entry['query']), 'select'))
            ->count();

        $this->assertLessThanOrEqual(12, $selectCount, (string) collect(DB::getQueryLog())->pluck('query')->implode(PHP_EOL));
    }

    public function test_public_badge_endpoint_returns_not_found_when_public_label_is_disabled(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'public_label_enabled' => false,
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertNotFound();
    }

    public function test_public_badge_endpoint_returns_unknown_state_when_monitoring_has_no_results_yet(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Fresh API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subMinutes(30),
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('name', 'Fresh API');
        $testResponse->assertJsonPath('status', MonitoringStatus::UNKNOWN->value);
        $testResponse->assertJsonPath('status_label', 'UNKNOWN');
        $testResponse->assertJsonPath('status_code', null);
        $testResponse->assertJsonPath('status_identifier', 'status.unknown');
        $testResponse->assertJsonPath('status_key', 'notifications.status.unknown');
        $testResponse->assertJsonPath('checked_at', null);
        $testResponse->assertJsonPath('checked_at_human', null);
        $testResponse->assertJsonPath('uptime.7_days', null);
        $testResponse->assertJsonPath('uptime.30_days', null);
        $testResponse->assertJsonPath('uptime.90_days', null);
        $testResponse->assertJsonPath('uptime.365_days', null);
    }

    public function test_public_badge_endpoint_returns_maintenance_status_metadata_during_an_active_maintenance_window(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Scheduled Maintenance API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'maintenance_from' => Date::now()->subHour(),
            'maintenance_until' => Date::now()->addHour(),
            'created_at' => Date::now()->subDays(10),
        ]);

        $checkedAt = Date::now()->subMinutes(5);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'created_at' => $checkedAt,
            'updated_at' => $checkedAt,
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('status', MonitoringStatus::UP->value);
        $testResponse->assertJsonPath('status_label', 'UP');
        $testResponse->assertJsonPath('status_identifier', 'status.maintenance');
        $testResponse->assertJsonPath('status_key', 'notifications.status.maintenance');
    }

    public function test_public_badge_endpoint_returns_maintenance_meta_when_monitoring_has_no_results_yet(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Fresh API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subMinutes(30),
            'maintenance_from' => Date::now()->subMinutes(10),
            'maintenance_until' => Date::now()->addMinutes(20),
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('name', 'Fresh API');
        $testResponse->assertJsonPath('status', MonitoringStatus::UNKNOWN->value);
        $testResponse->assertJsonPath('status_label', 'UNKNOWN');
        $testResponse->assertJsonPath('status_code', null);
        $testResponse->assertJsonPath('status_identifier', 'status.maintenance');
        $testResponse->assertJsonPath('status_key', 'notifications.status.maintenance');
        $testResponse->assertJsonPath('checked_at', null);
        $testResponse->assertJsonPath('checked_at_human', null);
        $testResponse->assertJsonPath('uptime.7_days', null);
        $testResponse->assertJsonPath('uptime.30_days', null);
        $testResponse->assertJsonPath('uptime.90_days', null);
        $testResponse->assertJsonPath('uptime.365_days', null);
    }

    public function test_public_badge_endpoint_returns_maintenance_meta_for_open_ended_maintenance_without_results(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Fresh API',
            'type' => MonitoringType::HTTP,
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subMinutes(30),
            'maintenance_from' => Date::now()->subMinutes(10),
            'maintenance_until' => null,
        ]);

        $testResponse = $this->getJson('/api/public/monitorings/' . $monitoring->id . '/badge');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('name', 'Fresh API');
        $testResponse->assertJsonPath('status', MonitoringStatus::UNKNOWN->value);
        $testResponse->assertJsonPath('status_label', 'UNKNOWN');
        $testResponse->assertJsonPath('status_code', null);
        $testResponse->assertJsonPath('status_identifier', 'status.maintenance');
        $testResponse->assertJsonPath('status_key', 'notifications.status.maintenance');
        $testResponse->assertJsonPath('checked_at', null);
        $testResponse->assertJsonPath('checked_at_human', null);
        $testResponse->assertJsonPath('uptime.7_days', null);
        $testResponse->assertJsonPath('uptime.30_days', null);
        $testResponse->assertJsonPath('uptime.90_days', null);
        $testResponse->assertJsonPath('uptime.365_days', null);
    }
}
