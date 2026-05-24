<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringFailureConfirmationThresholdTest extends TestCase
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

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_default_threshold_opens_incident_on_first_failure(): void
    {
        Date::setTestNow('2026-05-24 09:00:00');

        $monitoring = Monitoring::factory()->for($this->user)->create([
            'failure_confirmation_threshold' => 1,
        ]);

        $this->recordResponse($monitoring, MonitoringStatus::DOWN);

        $incident = Incident::query()->where('monitoring_id', $monitoring->id)->firstOrFail();
        $this->assertNull($incident->up_at);
        $this->assertSame(Date::now()->toIso8601String(), $incident->down_at->toIso8601String());
    }

    public function test_threshold_delays_incident_until_consecutive_failures_are_confirmed(): void
    {
        Date::setTestNow('2026-05-24 09:00:00');

        $monitoring = Monitoring::factory()->for($this->user)->create([
            'failure_confirmation_threshold' => 3,
        ]);

        $this->recordResponse($monitoring, MonitoringStatus::DOWN);
        $this->assertSame(0, Incident::query()->where('monitoring_id', $monitoring->id)->count());

        Date::setTestNow(Date::now()->addMinute());
        $this->recordResponse($monitoring, MonitoringStatus::DOWN);
        $this->assertSame(0, Incident::query()->where('monitoring_id', $monitoring->id)->count());

        Date::setTestNow(Date::now()->addMinute());
        $this->recordResponse($monitoring, MonitoringStatus::DOWN);

        $this->assertDatabaseHas('incidents', [
            'monitoring_id' => $monitoring->id,
            'up_at' => null,
        ]);
    }

    public function test_successful_check_resets_consecutive_failure_confirmation(): void
    {
        Date::setTestNow('2026-05-24 09:00:00');

        $monitoring = Monitoring::factory()->for($this->user)->create([
            'failure_confirmation_threshold' => 3,
        ]);

        foreach ([MonitoringStatus::DOWN, MonitoringStatus::DOWN, MonitoringStatus::UP, MonitoringStatus::DOWN, MonitoringStatus::DOWN] as $status) {
            $this->recordResponse($monitoring, $status);
            Date::setTestNow(Date::now()->addMinute());
        }

        $this->assertSame(0, Incident::query()->where('monitoring_id', $monitoring->id)->count());

        $this->recordResponse($monitoring, MonitoringStatus::DOWN);

        $this->assertDatabaseHas('incidents', [
            'monitoring_id' => $monitoring->id,
            'up_at' => null,
        ]);
    }

    public function test_successful_check_closes_confirmed_incident(): void
    {
        Date::setTestNow('2026-05-24 09:00:00');

        $monitoring = Monitoring::factory()->for($this->user)->create([
            'failure_confirmation_threshold' => 2,
        ]);

        $this->recordResponse($monitoring, MonitoringStatus::DOWN);
        Date::setTestNow(Date::now()->addMinute());
        $this->recordResponse($monitoring, MonitoringStatus::DOWN);

        $incident = Incident::query()->where('monitoring_id', $monitoring->id)->firstOrFail();
        $this->assertNull($incident->up_at);

        Date::setTestNow(Date::now()->addMinute());
        $this->recordResponse($monitoring, MonitoringStatus::UP);

        $incident->refresh();
        $this->assertSame(Date::now()->toIso8601String(), $incident->up_at?->toIso8601String());
    }

    public function test_monitoring_form_persists_failure_confirmation_threshold(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'Noisy API',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://example.com/health',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'expected_http_statuses' => '200-299',
            'preferred_location' => $this->serverInstance->code,
            'failure_confirmation_threshold' => 3,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $this->assertDatabaseHas('monitorings', [
            'user_id' => $this->user->id,
            'name' => 'Noisy API',
            'failure_confirmation_threshold' => 3,
        ]);
    }

    public function test_failure_confirmation_threshold_must_be_within_supported_range(): void
    {
        $testResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), [
                'name' => 'Noisy API',
                'type' => MonitoringType::HTTP->value,
                'target' => 'https://example.com/health',
                'status' => MonitoringLifecycleStatus::ACTIVE->value,
                'timeout' => 5,
                'expected_http_statuses' => '200-299',
                'preferred_location' => $this->serverInstance->code,
                'failure_confirmation_threshold' => 11,
            ]);

        $testResponse->assertRedirect(route('monitorings.create'));
        $testResponse->assertSessionHasErrors(['failure_confirmation_threshold']);
    }

    private function recordResponse(Monitoring $monitoring, MonitoringStatus $monitoringStatus): void
    {
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => $monitoringStatus,
            'http_status_code' => match ($monitoringStatus) {
                MonitoringStatus::UP => 200,
                MonitoringStatus::DOWN => 503,
                MonitoringStatus::UNKNOWN => null,
            },
            'response_time' => $monitoringStatus === MonitoringStatus::UP ? 120.0 : null,
        ]);
    }
}
