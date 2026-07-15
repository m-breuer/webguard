<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringResponse;
use App\Models\NotificationChannelDelivery;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    public function test_new_user_sees_a_clear_dashboard_empty_state(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.empty.title'))
            ->assertSeeText(__('dashboard.empty.description'))
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

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeText(__('dashboard.state.degraded.title'))
            ->assertSeeText(__('dashboard.summary.healthy'))
            ->assertSeeText(__('dashboard.summary.down'))
            ->assertSeeText(__('dashboard.summary.unknown'))
            ->assertSeeText(__('dashboard.summary.paused'))
            ->assertSeeText('Checkout API')
            ->assertSeeText('Unknown API')
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

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.state.healthy.title'))
            ->assertSeeText(__('dashboard.attention.empty'))
            ->assertDontSeeText(__('dashboard.attention.incident', ['name' => $monitoring->name]));
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

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.maintenance.heading'))
            ->assertSeeText(__('dashboard.maintenance.upcoming'))
            ->assertSeeText(__('dashboard.attention.delivery', ['count' => 1]))
            ->assertSeeText(__('dashboard.trend.heading'))
            ->assertSeeHtml('href="' . route('maintenance.index') . '"')
            ->assertSeeHtml('href="' . route('notifications.index') . '"');
    }
}
