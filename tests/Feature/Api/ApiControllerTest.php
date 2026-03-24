<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_correct_interval_in_the_status_endpoint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/status');

        $response->assertOk();
        $response->assertJson(['interval' => 300]);
    }

    public function test_returns_status_metadata_and_translation_keys_in_status_endpoint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => 220.0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/status');

        $response->assertOk();
        $response->assertJsonPath('status_code', 503);
        $response->assertJsonPath('status_identifier', 'status.server_error');
        $response->assertJsonPath('status_key', 'notifications.status.server_error');
        $response->assertJsonPath('monitoring.name', $monitoring->name);
        $response->assertJsonPath('monitoring.target', $monitoring->target);
    }

    public function test_results_endpoint_exposes_http_status_code_for_historical_entries(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $liveCheckedAt = now()->subMinutes(3);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 123.4,
            'created_at' => $liveCheckedAt,
            'updated_at' => $liveCheckedAt,
        ]);

        $archivedCheckedAt = now()->subDay();
        DB::table('monitoring_response_archived')->insert([
            'id' => (string) Str::ulid(),
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
            'response_time' => 222.0,
            'created_at' => $archivedCheckedAt,
            'updated_at' => $archivedCheckedAt,
        ]);

        $response = $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/checks?limit=10');

        $response->assertOk();
        $response->assertJsonPath('meta.count', 2);
        $response->assertJsonPath('data.0.http_status_code', 204);
        $response->assertJsonPath('data.0.status_identifier', 'status.success');
        $response->assertJsonPath('data.1.http_status_code', 503);
        $response->assertJsonPath('data.1.status_identifier', 'status.server_error');
    }
}
