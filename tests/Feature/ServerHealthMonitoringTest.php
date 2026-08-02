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
use App\Services\MonitoringHealthEvaluator;
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
        $testResponse->assertSee(route('scribe'));
    }

    public function test_api_reference_uses_the_renamed_documentation_path(): void
    {
        $this->assertSame('/api/reference', config('scribe.laravel.docs_url'));
        $this->assertSame(url('/api/reference'), route('scribe'));

        $this->get('/api/docs')
            ->assertMovedPermanently()
            ->assertRedirect(url('/api/reference'));
    }

    public function test_it_creates_server_health_monitoring_with_generated_report_url(): void
    {
        $testResponse = $this->actingAs($this->user)->post(route('monitorings.store'), [
            'name' => 'Production Server',
            'type' => MonitoringType::SERVER_HEALTH->value,
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
            'server_health_cpu_threshold_percent' => 85,
            'server_health_ram_threshold_percent' => 80,
            'server_health_storage_threshold_percent' => 95,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Production Server')->firstOrFail();

        $this->assertSame(MonitoringType::SERVER_HEALTH, $monitoring->type);
        $this->assertNotNull($monitoring->server_health_token);
        $this->assertSame(
            route('v1.server-health.store', ['token' => $monitoring->server_health_token]),
            $monitoring->target
        );
        $this->assertSame(85.0, $monitoring->server_health_cpu_threshold_percent);
        $this->assertSame(80.0, $monitoring->server_health_ram_threshold_percent);
        $this->assertSame(95.0, $monitoring->server_health_storage_threshold_percent);
    }

    public function test_it_updates_server_health_thresholds(): void
    {
        $monitoring = $this->createServerHealthMonitoring([
            'server_health_cpu_threshold_percent' => 90,
            'server_health_ram_threshold_percent' => 90,
            'server_health_storage_threshold_percent' => 90,
        ]);

        $testResponse = $this->actingAs($this->user)->patch(route('monitorings.update', $monitoring), [
            'name' => 'Production Server',
            'type' => MonitoringType::SERVER_HEALTH->value,
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'preferred_location' => $this->serverInstance->code,
            'server_health_cpu_threshold_percent' => 92.5,
            'server_health_ram_threshold_percent' => 82.25,
            'server_health_storage_threshold_percent' => 97,
        ]);

        $testResponse->assertRedirect(route('monitorings.show', $monitoring));

        $monitoring->refresh();
        $this->assertSame(92.5, $monitoring->server_health_cpu_threshold_percent);
        $this->assertSame(82.25, $monitoring->server_health_ram_threshold_percent);
        $this->assertSame(97.0, $monitoring->server_health_storage_threshold_percent);
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

        $monitoringResponse = MonitoringResponse::query()->where('monitoring_id', $monitoring->id)->firstOrFail();
        $this->assertNull($monitoringResponse->status);
        $this->assertNull($monitoringResponse->http_status_code);
        $this->assertSame(42.5, $monitoringResponse->server_health_metrics['cpu_usage_percent']);
        $this->assertSame(68.2, $monitoringResponse->server_health_metrics['ram_usage_percent']);
        $this->assertSame(74.1, $monitoringResponse->server_health_metrics['storage_usage_percent']);
        $this->assertSame(MonitoringStatus::UP, resolve(MonitoringHealthEvaluator::class)->availabilityFor($monitoring, $monitoringResponse));
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
            'status' => null,
            'http_status_code' => null,
        ]);

        $this->assertSame(
            MonitoringStatus::DOWN,
            resolve(MonitoringHealthEvaluator::class)->availabilityFor($monitoring, MonitoringResponse::query()->sole())
        );
    }

    public function test_server_health_metrics_take_precedence_over_a_legacy_status(): void
    {
        $monitoring = $this->createServerHealthMonitoring();

        $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [
            'status' => MonitoringStatus::UP->value,
            'cpu_usage_percent' => 91.0,
        ])->assertOk()
            ->assertJsonPath('status', MonitoringStatus::DOWN->value);

        $response = MonitoringResponse::query()->sole();

        $this->assertNull($response->status);
        $this->assertSame(MonitoringStatus::DOWN, resolve(MonitoringHealthEvaluator::class)->availabilityFor($monitoring, $response));
    }

    public function test_server_health_endpoint_uses_custom_thresholds(): void
    {
        $monitoring = $this->createServerHealthMonitoring([
            'server_health_cpu_threshold_percent' => 95,
            'server_health_ram_threshold_percent' => 75,
            'server_health_storage_threshold_percent' => 98,
        ]);

        $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [
            'cpu_usage_percent' => 91.0,
            'ram_usage_percent' => 74.0,
            'storage_usage_percent' => 97.0,
        ])->assertOk()
            ->assertJsonPath('status', MonitoringStatus::UP->value)
            ->assertJsonPath('thresholds.cpu_usage_percent', 95)
            ->assertJsonPath('thresholds.ram_usage_percent', 75)
            ->assertJsonPath('thresholds.storage_usage_percent', 98);

        $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [
            'ram_usage_percent' => 75.0,
        ])->assertOk()
            ->assertJsonPath('status', MonitoringStatus::DOWN->value);

        $this->assertDatabaseHas('monitoring_response_results', [
            'monitoring_id' => $monitoring->id,
            'status' => null,
            'http_status_code' => null,
        ]);
    }

    public function test_server_health_thresholds_must_be_between_one_and_one_hundred(): void
    {
        $testResponse = $this->from(route('monitorings.create'))
            ->actingAs($this->user)
            ->post(route('monitorings.store'), [
                'name' => 'Production Server',
                'type' => MonitoringType::SERVER_HEALTH->value,
                'status' => MonitoringLifecycleStatus::ACTIVE->value,
                'preferred_location' => $this->serverInstance->code,
                'server_health_cpu_threshold_percent' => 0,
                'server_health_ram_threshold_percent' => 101,
                'server_health_storage_threshold_percent' => 90,
            ]);

        $testResponse->assertRedirect(route('monitorings.create'));
        $testResponse->assertSessionHasErrors([
            'server_health_cpu_threshold_percent',
            'server_health_ram_threshold_percent',
        ]);
    }

    public function test_server_health_endpoint_rejects_empty_reports(): void
    {
        $monitoring = $this->createServerHealthMonitoring();

        $this->postJson(route('v1.server-health.store', ['token' => $monitoring->server_health_token]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metrics']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createServerHealthMonitoring(array $overrides = []): Monitoring
    {
        $token = (string) fake()->unique()->uuid();

        return Monitoring::factory()
            ->serverHealth()
            ->for($this->user)
            ->create(array_merge([
                'preferred_location' => $this->serverInstance->code,
                'status' => MonitoringLifecycleStatus::ACTIVE,
                'server_health_token' => $token,
                'target' => route('v1.server-health.store', ['token' => $token]),
            ], $overrides));
    }
}
