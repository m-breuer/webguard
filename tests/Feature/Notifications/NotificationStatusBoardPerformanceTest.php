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

        $firstMonitoring = $this->createStatusBoardMonitoring($user, 503, Date::now()->copy()->subMinutes(4));
        $secondMonitoring = $this->createStatusBoardMonitoring($user, 204, Date::now()->copy()->subMinutes(2));

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

    public function test_status_board_orders_entries_by_notification_id_when_status_change_timestamps_match(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $createdAt = Date::now()->copy()->subMinute();

        $firstMonitoring = $this->createStatusBoardMonitoring(
            $user,
            503,
            $createdAt,
            '01ARZ3NDEKTSV4RRFFQ69G5FAV'
        );
        $selectedMonitoring = $this->createStatusBoardMonitoring(
            $user,
            204,
            $createdAt,
            '01ARZ3NDEKTSV4RRFFQ69G5FAW'
        );

        $this->actingAs($user);

        $entries = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: true, limit: 5);

        $this->assertSame([$selectedMonitoring->id, $firstMonitoring->id], $entries->pluck('monitoring_id')->all());
        $this->assertSame([
            '01ARZ3NDEKTSV4RRFFQ69G5FAW',
            '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ], $entries->pluck('notification_id')->all());
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

    public function test_status_board_uses_the_id_tie_breaker_when_status_change_timestamps_match(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $createdAt = Date::now()->subMinute();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 125.0,
            'created_at' => $createdAt->copy()->subMinute(),
            'updated_at' => $createdAt->copy()->subMinute(),
        ]);

        $firstNotification = new MonitoringNotification([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);
        $firstNotification->id = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $firstNotification->created_at = $createdAt;
        $firstNotification->updated_at = $createdAt;
        $firstNotification->save();

        $selectedNotification = new MonitoringNotification([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);
        $selectedNotification->id = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
        $selectedNotification->created_at = $createdAt;
        $selectedNotification->updated_at = $createdAt;
        $selectedNotification->save();

        $this->actingAs($user);

        $entry = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: true)->sole();

        $this->assertSame($selectedNotification->id, $entry['notification_id']);
        $this->assertSame('notifications.status_change.up', $entry['status_change_key']);
    }

    public function test_status_board_uses_the_id_tie_breaker_when_response_timestamps_match(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $checkedAt = Date::now()->subMinutes(2);

        $firstResponse = new MonitoringResponse([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => 180.0,
        ]);
        $firstResponse->id = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $firstResponse->created_at = $checkedAt;
        $firstResponse->updated_at = $checkedAt;
        $firstResponse->save();

        $selectedResponse = new MonitoringResponse([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 125.0,
        ]);
        $selectedResponse->id = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
        $selectedResponse->created_at = $checkedAt;
        $selectedResponse->updated_at = $checkedAt;
        $selectedResponse->save();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        $this->actingAs($user);

        $entry = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: true)->sole();

        $this->assertSame($selectedResponse->http_status_code, $entry['latest_status_code']);
        $this->assertSame($checkedAt->toIso8601String(), $entry['latest_checked_at']);
    }

    public function test_unread_status_board_uses_the_highest_unread_id_when_same_timestamp_also_has_newer_read_entries(): void
    {
        Date::setTestNow('2026-04-19 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $createdAt = Date::now()->subMinute();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 125.0,
            'created_at' => $createdAt->copy()->subMinute(),
            'updated_at' => $createdAt->copy()->subMinute(),
        ]);

        $firstUnreadNotification = new MonitoringNotification([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);
        $firstUnreadNotification->id = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $firstUnreadNotification->created_at = $createdAt;
        $firstUnreadNotification->updated_at = $createdAt;
        $firstUnreadNotification->save();

        $selectedUnreadNotification = new MonitoringNotification([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);
        $selectedUnreadNotification->id = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
        $selectedUnreadNotification->created_at = $createdAt;
        $selectedUnreadNotification->updated_at = $createdAt;
        $selectedUnreadNotification->save();

        $readNotification = new MonitoringNotification([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => true,
            'sent' => true,
        ]);
        $readNotification->id = '01ARZ3NDEKTSV4RRFFQ69G5FAX';
        $readNotification->created_at = $createdAt;
        $readNotification->updated_at = $createdAt;
        $readNotification->save();

        $this->actingAs($user);

        $entry = resolve(NotificationBoardService::class)->getStatusBoardEntries(showRead: false)->sole();

        $this->assertSame($selectedUnreadNotification->id, $entry['notification_id']);
        $this->assertSame('notifications.status_change.up', $entry['status_change_key']);
        $this->assertFalse($entry['read']);
    }

    private function createStatusBoardMonitoring(
        User $user,
        int $statusCode,
        CarbonInterface $notificationTime,
        ?string $notificationId = null
    ): Monitoring {
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringResponse::withoutEvents(fn (): MonitoringResponse => MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => $statusCode >= 500 ? MonitoringStatus::DOWN : MonitoringStatus::UP,
            'http_status_code' => $statusCode,
            'response_time' => 140.0,
            'created_at' => $notificationTime->copy()->subMinute(),
            'updated_at' => $notificationTime->copy()->subMinute(),
        ]));

        $notificationAttributes = [
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => $statusCode >= 500 ? 'DOWN' : 'UP',
            'read' => false,
            'sent' => true,
            'created_at' => $notificationTime,
            'updated_at' => $notificationTime,
        ];

        if ($notificationId !== null) {
            $notificationAttributes['id'] = $notificationId;
        }

        MonitoringNotification::query()->forceCreate($notificationAttributes);

        return $monitoring;
    }
}
