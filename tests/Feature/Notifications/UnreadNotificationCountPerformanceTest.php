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

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $firstMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $firstMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $secondMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $secondMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $deletedMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
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

        $count = resolve(NotificationBoardService::class)->getUnreadNotificationCount();

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_starts_with(mb_strtolower($query), 'select'))
            ->values();

        $this->assertSame(3, $count);
        $this->assertCount(1, $selectQueries);
        $this->assertTrue($selectQueries->contains(
            fn (string $query): bool => str_contains(mb_strtolower($query), 'count(distinct')
        ));
        $this->assertTrue($selectQueries->contains(
            fn (string $query): bool => str_contains(mb_strtolower($query), 'sum(case')
        ));
        $this->assertFalse($selectQueries->contains(
            fn (string $query): bool => str_contains($query, 'latestUnreadStatusChangeNotification')
        ));
    }

    public function test_unread_notification_count_returns_zero_without_notifications(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();

        $this->actingAs($user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $count = resolve(NotificationBoardService::class)->getUnreadNotificationCount();

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_starts_with(mb_strtolower($query), 'select'))
            ->values();

        $this->assertSame(0, $count);
        $this->assertCount(1, $selectQueries);
    }

    public function test_unread_notification_counts_by_user_use_one_grouped_aggregate_query(): void
    {
        $package = Package::factory()->create();
        $firstUser = User::factory()->for($package)->create();
        $secondUser = User::factory()->for($package)->create();

        $firstMonitoring = Monitoring::factory()->for($firstUser)->create();
        $secondMonitoring = Monitoring::factory()->for($firstUser)->create();
        $thirdMonitoring = Monitoring::factory()->for($secondUser)->create();
        $deletedMonitoring = Monitoring::factory()->for($secondUser)->create();

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $firstMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $firstMonitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $secondMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $thirdMonitoring->id,
            'type' => NotificationType::DOMAIN_EXPIRY,
            'message' => 'DOMAIN_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $deletedMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        $deletedMonitoring->delete();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $counts = resolve(NotificationBoardService::class)->getUnreadNotificationCountsByUser();

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_starts_with(mb_strtolower($query), 'select'))
            ->values();

        $this->assertSame(2, $counts->get($firstUser->id));
        $this->assertSame(1, $counts->get($secondUser->id));
        $this->assertCount(1, $selectQueries);
        $this->assertTrue($selectQueries->contains(
            fn (string $query): bool => str_contains(mb_strtolower($query), 'count(distinct')
        ));
        $this->assertTrue($selectQueries->contains(
            fn (string $query): bool => str_contains(mb_strtolower($query), 'sum(case')
        ));
    }
}
