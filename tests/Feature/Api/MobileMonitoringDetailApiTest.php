<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileMonitoringDetailApiTest extends TestCase
{
    public function test_authenticated_user_can_read_a_bounded_mobile_monitoring_detail_payload(): void
    {
        Date::setTestNow('2026-08-09 12:00:00');
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 120,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => now()->subHours(2),
            'up_at' => now()->subHour(),
        ]);
        MonitoringDomainResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'is_valid' => true,
            'expires_at' => now()->addYear(),
            'registrar' => 'Example Registrar',
            'checked_at' => now()->subMinute(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/mobile/monitorings/' . $monitoring->id)
            ->assertOk()
            ->assertJsonPath('data.summary.id', $monitoring->id)
            ->assertJsonPath('data.summary.name', 'Primary API')
            ->assertJsonPath('data.current_check.status', MonitoringStatus::UP->value)
            ->assertJsonPath('data.incidents.0.down_at', now()->subHours(2)->toIso8601String())
            ->assertJsonPath('data.domain.registrar', 'Example Registrar')
            ->assertJsonPath('meta.incidents.limit', 20)
            ->assertJsonPath('meta.sections.current_check.state', 'current')
            ->assertJsonPath('meta.sections.availability.state', 'empty')
            ->assertJsonPath('meta.sections.ssl.state', 'unavailable')
            ->assertJsonPath('meta.sections.domain.state', 'current');
    }

    public function test_mobile_monitoring_detail_paginates_incidents_deterministically(): void
    {
        Date::setTestNow('2026-08-09 12:00:00');
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        Incident::query()->create(['monitoring_id' => $monitoring->id, 'down_at' => now()->subHours(3)]);
        $second = Incident::query()->create(['monitoring_id' => $monitoring->id, 'down_at' => now()->subHours(2)]);
        Incident::query()->create(['monitoring_id' => $monitoring->id, 'down_at' => now()->subHour()]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/mobile/monitorings/' . $monitoring->id . '?incident_limit=1&incident_offset=1')
            ->assertOk()
            ->assertJsonPath('data.incidents.0.down_at', $second->down_at->toIso8601String())
            ->assertJsonPath('meta.incidents.offset', 1)
            ->assertJsonPath('meta.incidents.has_more', true)
            ->assertJsonPath('meta.incidents.next_offset', 2);
    }

    public function test_mobile_monitoring_detail_hides_foreign_monitorings_and_rejects_invalid_ranges(): void
    {
        Package::factory()->create();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $monitoring = Monitoring::factory()->for($owner)->create();

        $this->getJson('/api/v1/mobile/monitorings/' . $monitoring->id)->assertUnauthorized();

        Sanctum::actingAs($otherUser);

        $this->getJson('/api/v1/mobile/monitorings/' . $monitoring->id)->assertNotFound();

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/mobile/monitorings/' . $monitoring->id . '?days=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['days']);
    }
}
