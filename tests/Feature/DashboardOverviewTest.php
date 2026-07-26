<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\StatusPageComponentSource;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\NotificationChannelDelivery;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    public function test_dashboard_renders_a_shell_even_when_legacy_async_parameters_are_present(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->for($user)->create();

        $this->actingAs($user)->get(route('dashboard', ['async' => 1]))
            ->assertOk()
            ->assertSeeHtml('data-dashboard-loader')
            ->assertSeeHtml('x-data="dashboardLoader()"')
            ->assertSeeHtml('data-api-endpoint="/api/v1/internal/ui/dashboard"')
            ->assertDontSeeHtml('data-endpoint="/dashboard"')
            ->assertSeeHtml('data-loading-skeleton="dashboard"')
            ->assertDontSeeHtml('data-signal-room');
    }

    public function test_dashboard_api_paginates_the_service_landscape_without_changing_the_global_summary(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 20]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->count(11)->for($user)->sequence(
            fn ($sequence) => ['name' => sprintf('Service %02d', $sequence->index + 1)],
        )->create();

        $testResponse = $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'));
        $secondPage = $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard', ['service_page' => 2]));

        $testResponse->assertOk()
            ->assertJsonPath('data.summary.total', 11)
            ->assertJsonPath('data.services.0.name', 'Service 01')
            ->assertJsonMissing(['name' => 'Service 11'])
            ->assertJsonPath('meta.service_pagination.current_page', 1)
            ->assertJsonPath('meta.service_pagination.last_page', 2);
        $secondPage->assertOk()
            ->assertJsonPath('data.summary.total', 11)
            ->assertJsonPath('data.services.0.name', 'Service 11')
            ->assertJsonPath('meta.service_pagination.current_page', 2);
    }

    public function test_new_user_receives_an_empty_dashboard_projection_with_create_capability(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.overall_state', 'new')
            ->assertJsonPath('data.summary.total', 0)
            ->assertJsonPath('data.capabilities.can_create_monitoring', true)
            ->assertJsonPath('data.services', []);
    }

    public function test_dashboard_api_summarizes_visible_monitoring_health_and_attention(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);

        $healthy = Monitoring::factory()->for($user)->create(['name' => 'Healthy API']);
        MonitoringResponse::query()->create([
            'monitoring_id' => $healthy->id,
            'status' => MonitoringStatus::UP,
        ]);

        $down = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        Incident::query()->create([
            'monitoring_id' => $down->id,
            'down_at' => Date::now()->subMinutes(20),
        ]);

        Monitoring::factory()->for($user)->create([
            'name' => 'Paused API',
            'status' => MonitoringLifecycleStatus::PAUSED,
        ]);
        Monitoring::factory()->for($user)->create(['name' => 'Unknown API']);
        Monitoring::factory()->for($otherUser)->create(['name' => 'Private API']);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.overall_state', 'degraded')
            ->assertJsonPath('data.summary.total', 4)
            ->assertJsonPath('data.summary.healthy', 1)
            ->assertJsonPath('data.summary.down', 1)
            ->assertJsonPath('data.summary.unknown', 1)
            ->assertJsonPath('data.summary.paused', 1)
            ->assertJsonFragment(['name' => 'Checkout API'])
            ->assertJsonFragment(['status' => MonitoringStatus::DOWN->value])
            ->assertJsonFragment(['name' => 'Unknown API'])
            ->assertJsonMissing(['name' => 'Private API']);
    }

    public function test_all_healthy_dashboard_has_no_attention_items(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Healthy API']);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.overall_state', 'healthy')
            ->assertJsonPath('data.attention', []);
    }

    public function test_open_incident_attention_includes_its_matching_status_page(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subMinutes(20),
        ]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Customer Status',
            'is_public' => true,
        ]);
        $statusPage->components()->create([
            'name' => 'API',
            'position' => 0,
            'source_type' => StatusPageComponentSource::MANUAL,
        ])->monitorings()->attach($monitoring->id, ['position' => 0]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.attention.0.type', 'incident')
            ->assertJsonPath('data.attention.0.status_page_id', $statusPage->id)
            ->assertJsonPath('data.attention.0.status_page_name', $statusPage->name);
    }

    public function test_dashboard_api_includes_maintenance_delivery_and_reliability_context(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Scheduled API',
            'maintenance_from' => Date::now()->addHour(),
        ]);

        MonitoringDailyResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'date' => Date::today(),
            'uptime_total' => 99,
            'downtime_total' => 1,
            'unknown_total' => 0,
            'uptime_percentage' => 99,
            'downtime_percentage' => 1,
            'unknown_percentage' => 0,
            'uptime_minutes' => 99,
            'downtime_minutes' => 1,
            'unknown_minutes' => 0,
            'incidents_count' => 1,
        ]);

        NotificationChannelDelivery::query()->create([
            'user_id' => $user->id,
            'channel' => 'webhook',
            'event_type' => 'incident',
            'status' => NotificationDeliveryStatus::FAILED,
            'error_message' => 'Webhook unavailable',
        ]);

        $this->actingAs($user)->getJson(route('api.v1.internal.ui.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.maintenance.0.monitoring_id', $monitoring->id)
            ->assertJsonPath('data.maintenance.0.status', 'upcoming')
            ->assertJsonPath('data.failed_delivery_count', 1)
            ->assertJsonPath('data.trend.6.date', '2026-07-15');
    }
}
