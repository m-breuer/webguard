<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class HeartbeatMonitoringTest extends TestCase
{
    private User $user;

    private ServerInstance $serverInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $this->user = User::factory()->create(['package_id' => $package->id]);

        $this->serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $this->serverInstance->update([
            'api_key_hash' => 'test-token-1234567890',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_heartbeat_monitoring_with_generated_ping_url(): void
    {
        Date::setTestNow('2026-04-18 12:00:00');

        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'Nightly Backup',
            'type' => MonitoringType::HEARTBEAT->value,
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
            'heartbeat_interval_minutes' => 60,
            'heartbeat_grace_minutes' => 10,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Nightly Backup')->firstOrFail();

        $this->assertSame(MonitoringType::HEARTBEAT, $monitoring->type);
        $this->assertNotNull($monitoring->heartbeat_token);
        $this->assertSame(60, $monitoring->heartbeat_interval_minutes);
        $this->assertSame(10, $monitoring->heartbeat_grace_minutes);
        $this->assertSame(
            route('monitorings.heartbeat.ping', ['token' => $monitoring->heartbeat_token]),
            $monitoring->target
        );
    }

    public function test_heartbeat_ping_records_success_and_updates_status_api_timestamps(): void
    {
        Date::setTestNow('2026-04-18 12:00:00');

        $monitoring = $this->createHeartbeatMonitoring();

        $testResponse = $this->getJson(route('monitorings.heartbeat.ping', ['token' => $monitoring->heartbeat_token]));

        $testResponse->assertOk()
            ->assertJsonPath('message', 'Heartbeat accepted.');

        $monitoring->refresh();

        $this->assertSame(Date::now()->toIso8601String(), $monitoring->heartbeat_last_ping_at?->toIso8601String());
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP->value,
            'http_status_code' => 200,
        ]);

        $statusResponse = $this->actingAs($this->user)->getJson('/api/monitorings/' . $monitoring->id . '/status');

        $statusResponse->assertOk()
            ->assertJsonPath('status', MonitoringStatus::UP->value)
            ->assertJsonPath('checked_at', Date::now()->toIso8601String())
            ->assertJsonPath('next', Date::now()->copy()->addHour()->toIso8601String())
            ->assertJsonPath('interval', 3600);
    }

    public function test_heartbeat_ping_recovers_open_incident_after_missed_window(): void
    {
        Date::setTestNow('2026-04-18 12:00:00');

        $monitoring = $this->createHeartbeatMonitoring([
            'created_at' => Date::now()->subHours(2),
            'updated_at' => Date::now()->subHours(2),
        ]);

        $this->artisan('monitoring:evaluate-heartbeats')->assertSuccessful();

        $incident = Incident::query()->where('monitoring_id', $monitoring->id)->firstOrFail();
        $this->assertNull($incident->up_at);

        Date::setTestNow('2026-04-18 12:02:00');

        $this->getJson(route('monitorings.heartbeat.ping', ['token' => $monitoring->heartbeat_token]))
            ->assertOk();

        $incident->refresh();

        $this->assertNotNull($incident->up_at);
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
        ]);
        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP->value,
            'http_status_code' => 200,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createHeartbeatMonitoring(array $overrides = []): Monitoring
    {
        $token = (string) fake()->unique()->uuid();

        return Monitoring::factory()
            ->heartbeat()
            ->for($this->user)
            ->create(array_merge([
                'preferred_location' => $this->serverInstance->code,
                'status' => MonitoringLifecycleStatus::ACTIVE,
                'heartbeat_token' => $token,
                'target' => route('monitorings.heartbeat.ping', ['token' => $token]),
            ], $overrides));
    }
}
