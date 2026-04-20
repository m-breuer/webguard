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
use App\Services\NotificationBoardService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationStatusBoardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_board_entries_load_in_a_single_select_query(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();

        $firstMonitoring = $this->createStatusBoardMonitoring($user, 503, Date::now()->subMinutes(4));
        $secondMonitoring = $this->createStatusBoardMonitoring($user, 204, Date::now()->subMinutes(2));

        $this->actingAs($user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $entries = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: true, limit: 5);

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_starts_with(mb_strtolower($query), 'select'))
            ->values();

        $this->assertCount(1, $selectQueries);
        $this->assertSame([$secondMonitoring->id, $firstMonitoring->id], $entries->pluck('monitoring_id')->all());
    }

    public function test_unread_status_board_keeps_latest_unread_status_change_when_newer_read_entry_exists(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => 180.0,
            'created_at' => Date::now()->subMinutes(6),
            'updated_at' => Date::now()->subMinutes(6),
        ]);

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => true,
            'sent' => true,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        $this->actingAs($user);

        $entry = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: false)->sole();

        $this->assertSame($monitoringNotification->id, $entry['notification_id']);
        $this->assertSame('notifications.status_change.down', $entry['status_change_key']);
        $this->assertFalse($entry['read']);
    }

    public function test_status_board_prefers_newest_notification_id_when_status_changes_share_a_timestamp(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $sharedTimestamp = Date::now()->subMinute();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 95.0,
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
            'created_at' => $sharedTimestamp,
            'updated_at' => $sharedTimestamp,
        ]);

        $latestNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
            'created_at' => $sharedTimestamp,
            'updated_at' => $sharedTimestamp,
        ]);

        $this->actingAs($user);

        $entry = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: true)->sole();

        $this->assertSame($latestNotification->id, $entry['notification_id']);
        $this->assertSame('notifications.status_change.up', $entry['status_change_key']);
    }

    private function createStatusBoardMonitoring(User $user, int $statusCode, CarbonInterface $notificationTime): Monitoring
    {
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => $statusCode >= 500 ? MonitoringStatus::DOWN : MonitoringStatus::UP,
            'http_status_code' => $statusCode,
            'response_time' => 140.0,
            'created_at' => $notificationTime->copy()->subMinute(),
            'updated_at' => $notificationTime->copy()->subMinute(),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => $statusCode >= 500 ? 'DOWN' : 'UP',
            'read' => false,
            'sent' => true,
            'created_at' => $notificationTime,
            'updated_at' => $notificationTime,
        ]);

        return $monitoring;
    }
}
