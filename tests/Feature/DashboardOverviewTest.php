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
    public function test_dashboard_renders_a_shell_before_loading_expensive_components(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->for($user)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeHtml('data-dashboard-loader')
            ->assertSeeHtml('x-data="dashboardLoader()"')
            ->assertSeeHtml('data-endpoint="/dashboard"')
            ->assertSeeText(__('app.loading'))
            ->assertDontSeeHtml('data-signal-room');
    }

    public function test_dashboard_service_landscape_is_paginated_without_changing_the_global_summary(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 20]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->count(11)->for($user)->sequence(
            fn ($sequence) => ['name' => sprintf('Service %02d', $sequence->index + 1)],
        )->create();

        $testResponse = $this->actingAs($user)->get(route('dashboard', ['async' => 1]));
        $secondPage = $this->actingAs($user)->get(route('dashboard', ['async' => 1, 'service_page' => 2]));

        $testResponse->assertOk()
            ->assertSeeText('11 ' . __('dashboard.signal_room.active_services'))
            ->assertSeeText('Service 01')
            ->assertDontSeeText('Service 11')
            ->assertSeeText('1 / 2')
            ->assertSeeHtml('data-pagination-async')
            ->assertSeeHtml('href="/dashboard?');
        $secondPage->assertOk()
            ->assertSeeText('Service 11')
            ->assertSeeText('2 / 2')
            ->assertSeeHtml('service_page=1');
    }

    public function test_dashboard_service_landscape_page_can_be_loaded_as_a_bounded_fragment(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 20]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->count(11)->for($user)->sequence(
            fn ($sequence) => ['name' => sprintf('Service %02d', $sequence->index + 1)],
        )->create();

        $this->actingAs($user)->get(route('dashboard', [
            'async' => 1,
            'service_fragment' => 1,
            'service_page' => 2,
        ]))
            ->assertOk()
            ->assertSeeHtml('id="dashboard-service-list"')
            ->assertSeeHtml('href="/dashboard?')
            ->assertSeeText('Service 11')
            ->assertDontSeeText('Service 01')
            ->assertDontSeeText(__('dashboard.greeting', ['name' => $user->name]));
    }

    public function test_new_user_sees_a_clear_dashboard_empty_state(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $this->actingAs($user)->get(route('dashboard', ['async' => 1]))
            ->assertOk()
            ->assertSeeText(__('dashboard.empty.title'))
            ->assertSeeText(__('dashboard.empty.description'))
            ->assertSeeHtml('data-form-modal-name="monitoring-form-modal"')
            ->assertSeeHtml('data-form-modal="monitoring-form-modal"')
            ->assertSeeHtml('href="' . route('monitorings.create') . '"');
    }

    public function test_dashboard_summarizes_health_attention_and_recent_incidents_for_visible_monitorings(): void
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

        $testResponse = $this->actingAs($user)->get(route('dashboard', ['async' => 1]));

        $testResponse->assertOk()
            ->assertSeeText(__('dashboard.greeting', ['name' => $user->name]))
            ->assertDontSeeText(__('dashboard.open_monitorings'))
            ->assertSeeText('Hi ' . $user->name)
            ->assertDontSeeText('Guten Morgen, ' . $user->name)
            ->assertSeeText(__('dashboard.signal_room.heading'))
            ->assertSeeText(__('dashboard.signal_room.service_landscape'))
            ->assertSeeHtml('overflow-x-auto px-1 py-2')
            ->assertSeeHtml('focus-visible:ring-2')
            ->assertSeeText(__('dashboard.signal_room.context.heading'))
            ->assertSeeText(__('dashboard.signal_room.context.surfaces_heading'))
            ->assertSeeText(__('dashboard.next_action.heading'))
            ->assertSeeText(__('dashboard.state.degraded.title'))
            ->assertSeeText(__('dashboard.summary.healthy'))
            ->assertSeeText(__('dashboard.summary.down'))
            ->assertSeeText(__('dashboard.summary.unknown'))
            ->assertSeeText(__('dashboard.summary.paused'))
            ->assertSeeText('Checkout API')
            ->assertSeeText(__('dashboard.signal_room.statuses.down'))
            ->assertSeeHtml('x-data="signalRoom(')
            ->assertSeeText('Unknown API')
            ->assertSeeText(__('dashboard.attention.open'))
            ->assertSeeText(__('dashboard.incidents.open'))
            ->assertDontSeeText('Private API')
            ->assertSeeHtml('href="' . route('monitorings.show', $down) . '"')
            ->assertSeeHtml('href="' . route('incidents.analytics') . '"');
    }

    public function test_all_healthy_dashboard_has_a_reassuring_empty_attention_state(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Healthy API']);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
        ]);

        $this->actingAs($user)->get(route('dashboard', ['async' => 1]))
            ->assertOk()
            ->assertSeeText(__('dashboard.state.healthy.title'))
            ->assertSeeText(__('dashboard.attention.empty'))
            ->assertDontSeeText(__('dashboard.attention.incident', ['name' => $monitoring->name]));
    }

    public function test_open_incident_attention_item_links_to_the_matching_status_page_workbench(): void
    {
        Date::setTestNow('2026-07-15 12:00:00');
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $incident = Incident::query()->create([
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

        $incidentWorkspaceUrl = route('status-pages.show', [
            'statusPage' => $statusPage,
            'incident_id' => $incident->id,
        ]) . '#incident-workbench-' . $incident->id;

        $this->actingAs($user)->get(route('dashboard', ['async' => 1]))
            ->assertOk()
            ->assertSeeHtml('href="' . $incidentWorkspaceUrl . '"')
            ->assertSeeText(__('dashboard.attention.open_incident'))
            ->assertSeeText(__('dashboard.attention.status_page', ['name' => $statusPage->name]));
    }

    public function test_dashboard_includes_maintenance_delivery_and_reliability_context(): void
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

        $this->actingAs($user)->get(route('dashboard', ['async' => 1]))
            ->assertOk()
            ->assertSeeText(__('dashboard.maintenance.heading'))
            ->assertSeeText(__('dashboard.maintenance.upcoming'))
            ->assertSeeText(__('dashboard.attention.delivery', ['count' => 1]))
            ->assertSeeText(__('dashboard.trend.heading'))
            ->assertSeeHtml('href="' . route('maintenance.index') . '"')
            ->assertSeeHtml('href="' . route('notifications.index') . '"');
    }
}
