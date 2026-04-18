<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use App\Services\NotificationBoardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnreadNotificationCountPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_notification_count_uses_distinct_monitoring_aggregate_for_status_changes(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $otherUser = User::factory()->for($package)->create();

        $firstMonitoring = Monitoring::factory()->for($user)->create();
        $secondMonitoring = Monitoring::factory()->for($user)->create();
        $deletedMonitoring = Monitoring::factory()->for($user)->create();
        $otherUserMonitoring = Monitoring::factory()->for($otherUser)->create();

        MonitoringNotification::withoutGlobalScopes()->create([
            'monitoring_id' => $firstMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::withoutGlobalScopes()->create([
            'monitoring_id' => $firstMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::withoutGlobalScopes()->create([
            'monitoring_id' => $secondMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::withoutGlobalScopes()->create([
            'monitoring_id' => $secondMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::withoutGlobalScopes()->create([
            'monitoring_id' => $deletedMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::withoutGlobalScopes()->create([
            'monitoring_id' => $otherUserMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        $deletedMonitoring->delete();

        $this->actingAs($user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $count = app(NotificationBoardService::class)->getUnreadNotificationCount();

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_starts_with(mb_strtolower($query), 'select'))
            ->values();

        $this->assertSame(3, $count);
        $this->assertCount(2, $selectQueries);
        $this->assertTrue($selectQueries->contains(
            fn (string $query): bool => str_contains(mb_strtolower($query), 'count(distinct')
        ));
        $this->assertFalse($selectQueries->contains(
            fn (string $query): bool => str_contains($query, 'latestUnreadStatusChangeNotification')
        ));
    }
}
