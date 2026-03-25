<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\MonitoringStatus;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class NotificationStatusBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_board_shows_only_latest_status_change_per_monitoring(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 100.5,
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ]);

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinutes(10),
            'updated_at' => Date::now()->subMinutes(10),
        ]);

        $latestNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index', ['show_read' => true]));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('id="' . $latestNotification->id . '"');
        $testResponse->assertDontSeeHtml('id="' . $monitoringNotification->id . '"');
    }

    public function test_status_board_api_returns_status_category_keys_and_badges(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();

        $entries = [
            'success' => $this->createStatusBoardMonitoring($user, 204)->id,
            'redirect' => $this->createStatusBoardMonitoring($user, 302)->id,
            'client_error' => $this->createStatusBoardMonitoring($user, 429)->id,
            'server_error' => $this->createStatusBoardMonitoring($user, 503)->id,
            'unknown' => $this->createStatusBoardMonitoring($user, null)->id,
            'maintenance' => $this->createStatusBoardMonitoring($user, null, true)->id,
        ];

        $testResponse = $this->actingAs($user)->getJson('/api/notifications/status-board?show_read=1&limit=20');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('meta.count', 6);

        $dataByMonitoring = collect($testResponse->json('data'))->keyBy('monitoring_id');

        $this->assertSame('status.success', $dataByMonitoring[$entries['success']]['status_identifier']);
        $this->assertSame('success', $dataByMonitoring[$entries['success']]['badge_type']);

        $this->assertSame('status.redirect', $dataByMonitoring[$entries['redirect']]['status_identifier']);
        $this->assertSame('info', $dataByMonitoring[$entries['redirect']]['badge_type']);

        $this->assertSame('status.client_error', $dataByMonitoring[$entries['client_error']]['status_identifier']);
        $this->assertSame('warning', $dataByMonitoring[$entries['client_error']]['badge_type']);

        $this->assertSame('status.server_error', $dataByMonitoring[$entries['server_error']]['status_identifier']);
        $this->assertSame('danger', $dataByMonitoring[$entries['server_error']]['badge_type']);

        $this->assertSame('status.unknown', $dataByMonitoring[$entries['unknown']]['status_identifier']);
        $this->assertSame('neutral', $dataByMonitoring[$entries['unknown']]['badge_type']);

        $this->assertSame('status.maintenance', $dataByMonitoring[$entries['maintenance']]['status_identifier']);
        $this->assertSame('neutral', $dataByMonitoring[$entries['maintenance']]['badge_type']);
    }

    public function test_status_board_respects_active_locale_for_labels_and_status_texts(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create(['locale' => 'de']);
        $monitoring = $this->createStatusBoardMonitoring($user, 503);

        $testResponse = $this->actingAs($user)->get(route('notifications.index', ['show_read' => true]));

        $testResponse->assertOk();
        $testResponse->assertSee('Server-Fehler');
        $testResponse->assertSee('Letzte Prüfung');
        $testResponse->assertSee($monitoring->name);
    }

    private function createStatusBoardMonitoring(User $user, ?int $statusCode, bool $maintenance = false): Monitoring
    {
        $monitoring = Monitoring::factory()->for($user)->create([
            'maintenance_from' => $maintenance ? Date::now()->subHour() : null,
            'maintenance_until' => $maintenance ? Date::now()->addHour() : null,
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => $statusCode,
            'response_time' => 250.0,
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        return $monitoring;
    }
}
