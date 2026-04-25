<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ServerInstanceHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_successful_instance_authentication_records_last_seen_timestamp(): void
    {
        Date::setTestNow('2026-04-25 08:00:00');

        $serverInstance = ServerInstance::query()->create([
            'code' => 'health-1',
            'ip_address' => '192.0.2.10',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
        ]);

        $testResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'valid-instance-key',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $serverInstance->code]));

        $testResponse->assertOk();

        $this->assertSame(
            Date::now()->toDateTimeString(),
            $serverInstance->fresh()->last_seen_at?->toDateTimeString()
        );
    }

    public function test_failed_instance_authentication_does_not_record_last_seen_timestamp(): void
    {
        Date::setTestNow('2026-04-25 08:00:00');

        $serverInstance = ServerInstance::query()->create([
            'code' => 'health-2',
            'ip_address' => '192.0.2.11',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
        ]);

        $testResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'wrong-instance-key',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $serverInstance->code]));

        $testResponse->assertUnauthorized();
        $this->assertNull($serverInstance->fresh()->last_seen_at);
    }

    public function test_last_seen_updates_are_throttled_for_frequent_instance_requests(): void
    {
        config(['monitoring.instance_seen_write_throttle_seconds' => 60]);
        Date::setTestNow('2026-04-25 08:00:00');

        $lastSeenAt = Date::now()->subSeconds(30);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'health-3',
            'ip_address' => '192.0.2.12',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => $lastSeenAt,
        ]);

        $testResponse = $this->withHeaders([
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => 'valid-instance-key',
        ])->getJson(route('v1.internal.monitorings.list', ['location' => $serverInstance->code]));

        $testResponse->assertOk();

        $this->assertSame(
            $lastSeenAt->toDateTimeString(),
            $serverInstance->fresh()->last_seen_at?->toDateTimeString()
        );
    }

    public function test_admin_server_instance_list_shows_health_states(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-04-25 08:00:00');

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        ServerInstance::query()->create([
            'code' => 'healthy-1',
            'ip_address' => '192.0.2.20',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(2),
        ]);
        ServerInstance::query()->create([
            'code' => 'stale-1',
            'ip_address' => '192.0.2.21',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(11),
        ]);
        ServerInstance::query()->create([
            'code' => 'new-1',
            'ip_address' => '192.0.2.22',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => null,
        ]);
        ServerInstance::query()->create([
            'code' => 'disabled-1',
            'ip_address' => '192.0.2.23',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => false,
            'last_seen_at' => Date::now(),
        ]);

        $testResponse = $this->actingAs($admin)->get(route('admin.server-instances.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('healthy-1');
        $testResponse->assertSeeText(__('admin.server_instances.health.healthy'));
        $testResponse->assertSeeText('stale-1');
        $testResponse->assertSeeText(__('admin.server_instances.health.stale'));
        $testResponse->assertSeeText('new-1');
        $testResponse->assertSeeText(__('admin.server_instances.health.never_seen'));
        $testResponse->assertSeeText('disabled-1');
        $testResponse->assertSeeText(__('admin.server_instances.health.inactive'));
    }
}
