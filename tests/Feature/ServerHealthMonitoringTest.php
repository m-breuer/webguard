<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ServerHealthMonitoringTest extends TestCase
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

    public function test_create_page_links_api_documentation_for_server_health_monitoring(): void
    {
        $testResponse = $this->actingAs($this->user)->get(route('monitorings.create'));

        $testResponse->assertOk();
        $testResponse->assertSee(__('monitoring.types.server_health'));
        $testResponse->assertSee(__('monitoring.form.server_health_docs_link'));
        $testResponse->assertSee(url('/api/docs'));
    }

    public function test_it_creates_server_health_monitoring_with_generated_report_url(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'Production Server',
            'type' => MonitoringType::SERVER_HEALTH->value,
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Production Server')->firstOrFail();

        $this->assertSame(MonitoringType::SERVER_HEALTH, $monitoring->type);
        $this->assertNotNull($monitoring->server_health_token);
        $this->assertSame(
            route('v1.server-health.store', ['token' => $monitoring->server_health_token]),
            $monitoring->target
        );
    }

    public function test_server_health_endpoint_stores_metrics_and_updates_last_report_timestamp(): void
    {
        Date::setTestNow('2026-05-14 12:00:00');

        $monitoring = $this->createServerHealthMonitoring();

        $testResponse = $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [
            'cpu_usage_percent' => 42.5,
            'ram_usage_percent' => 68.2,
            'storage_usage_percent' => 74.1,
            'load_average' => 1.42,
            'uptime_seconds' => 86400,
        ]);

        $testResponse->assertOk()
            ->assertJsonPath('message', 'Server health report accepted.')
            ->assertJsonPath('status', MonitoringStatus::UP->value)
            ->assertJsonPath('metrics.cpu_usage_percent', 42.5);

        $monitoring->refresh();
        $this->assertSame(Date::now()->toIso8601String(), $monitoring->server_health_last_reported_at?->toIso8601String());

        $response = MonitoringResponse::query()->where('monitoring_id', $monitoring->id)->firstOrFail();
        $this->assertSame(MonitoringStatus::UP, $response->status);
        $this->assertSame(200, $response->http_status_code);
        $this->assertSame(42.5, $response->server_health_metrics['cpu_usage_percent']);
        $this->assertSame(68.2, $response->server_health_metrics['ram_usage_percent']);
        $this->assertSame(74.1, $response->server_health_metrics['storage_usage_percent']);
    }

    public function test_server_health_endpoint_marks_report_down_when_usage_crosses_default_threshold(): void
    {
        $monitoring = $this->createServerHealthMonitoring();

        $testResponse = $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [
            'cpu_usage_percent' => 35.0,
            'ram_usage_percent' => 91.0,
            'storage_usage_percent' => 70.0,
        ]);

        $testResponse->assertOk()
            ->assertJsonPath('status', MonitoringStatus::DOWN->value);

        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
        ]);
    }

    public function test_server_health_endpoint_rejects_empty_reports(): void
    {
        $monitoring = $this->createServerHealthMonitoring();

        $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metrics']);
    }

    private function createServerHealthMonitoring(): Monitoring
    {
        $token = (string) fake()->unique()->uuid();

        return Monitoring::factory()
            ->serverHealth()
            ->for($this->user)
            ->create([
                'preferred_location' => $this->serverInstance->code,
                'status' => MonitoringLifecycleStatus::ACTIVE,
                'server_health_token' => $token,
                'target' => route('v1.server-health.store', ['token' => $token]),
            ]);
    }
}
