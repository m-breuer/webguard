<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ApiKeyAbility;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServerHealthRouteCompatibilityTest extends TestCase
{
    public function test_versioned_server_health_report_route_remains_compatible(): void
    {
        Date::setTestNow('2026-09-06 12:00:00');
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->serverHealth()->for($user)->create([
            'server_health_token' => 'legacy-report-token',
        ]);
        $reportId = (string) Str::uuid();

        $testResponse = $this->postJson('/api/v1/server-health/legacy-report-token', [
            'schema_version' => 1,
            'report_id' => $reportId,
            'sampled_at' => Date::now()->toIso8601String(),
            'host' => ['cpu_usage_percent' => 42.5],
        ]);

        $testResponse
            ->assertOk()
            ->assertJsonPath('message', 'Server health report accepted.')
            ->assertJsonPath('status', 'up');

        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'server_health_report_id' => $reportId,
        ]);
    }

    public function test_versioned_bearer_server_health_route_remains_compatible(): void
    {
        Date::setTestNow('2026-09-06 12:00:00');
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->serverHealth()->for($user)->create();
        $plainTextToken = $user->createToken(
            ApiKeyService::storedName('Server agent'),
            [ApiKeyAbility::SERVER_HEALTH_WRITE->value]
        )->plainTextToken;

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/server-health/monitorings/' . $monitoring->id, [
                'schema_version' => 1,
                'report_id' => (string) Str::uuid(),
                'sampled_at' => Date::now()->toIso8601String(),
                'host' => ['cpu_usage_percent' => 42.5],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Server health report accepted.')
            ->assertJsonPath('status', 'up');
    }
}
