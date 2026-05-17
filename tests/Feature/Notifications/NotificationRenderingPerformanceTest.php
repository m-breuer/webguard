<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\NotificationChannelDelivery;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationRenderingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_defers_status_change_messages_to_async_sections(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => true,
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();
        $testResponse->assertSee('loadInitialNotifications()');
        $testResponse->assertSeeHtml('id="status-change-notifications"');
        $testResponse->assertDontSee($monitoring->name);
    }

    public function test_notifications_index_does_not_load_section_queries_on_initial_render(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $user->id,
            'monitoring_notification_id' => null,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->map(static fn (string $query): string => mb_strtolower($query))
            ->filter(static fn (string $query): bool => str_starts_with($query, 'select'))
            ->values();

        $this->assertFalse($selectQueries->contains(
            fn (string $query): bool => str_contains($query, 'notification_channel_deliveries')
        ));
        $this->assertFalse($selectQueries->contains(
            fn (string $query): bool => str_contains($query, 'latest_status_notifications')
        ));
    }

    public function test_notification_board_tables_have_indexes_for_initial_page_pagination(): void
    {
        $this->assertTableHasIndexColumns(
            'monitoring_notifications',
            ['type', 'read', 'created_at', 'id']
        );

        $this->assertTableHasIndexColumns(
            'monitoring_notifications',
            ['monitoring_id', 'type', 'read', 'created_at', 'id']
        );

        $this->assertTableHasIndexColumns(
            'notification_channel_deliveries',
            ['user_id', 'created_at', 'id']
        );
    }

    public function test_notifications_index_uses_stable_id_order_for_matching_notification_timestamps(): void
    {
        Date::setTestNow('2026-05-17 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $createdAt = Date::now()->subMinute();

        $olderMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Lower id certificate']);
        $newerMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Higher id certificate']);

        MonitoringNotification::query()->forceCreate([
            'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'monitoring_id' => $olderMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        MonitoringNotification::query()->forceCreate([
            'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAW',
            'monitoring_id' => $newerMonitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
        ]);

        $testResponse->assertOk();
        $this->assertAppearsBefore(
            'Higher id certificate',
            'Lower id certificate',
            (string) $testResponse->json('html')
        );
    }

    public function test_notifications_index_uses_stable_id_order_for_matching_delivery_timestamps(): void
    {
        Date::setTestNow('2026-05-17 10:00:00');

        $package = Package::factory()->create();
        $user = User::factory()->for($package)->create();
        $createdAt = Date::now()->subMinute();

        NotificationChannelDelivery::query()->forceCreate([
            'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'user_id' => $user->id,
            'monitoring_notification_id' => null,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
            'payload' => [
                'monitoring' => [
                    'name' => 'Lower id delivery',
                    'target' => 'https://lower.example.test',
                ],
            ],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        NotificationChannelDelivery::query()->forceCreate([
            'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAW',
            'user_id' => $user->id,
            'monitoring_notification_id' => null,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
            'payload' => [
                'monitoring' => [
                    'name' => 'Higher id delivery',
                    'target' => 'https://higher.example.test',
                ],
            ],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => 'delivery_history',
            'offset' => 0,
        ]);

        $testResponse->assertOk();
        $this->assertAppearsBefore(
            'Higher id delivery',
            'Lower id delivery',
            (string) $testResponse->json('html')
        );
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertTableHasIndexColumns(string $table, array $columns): void
    {
        $indexColumns = collect(Schema::getIndexes($table))
            ->map(static fn (array $index): array => $index['columns'])
            ->values();

        $this->assertTrue(
            $indexColumns->contains($columns),
            sprintf(
                'Expected %s to have an index on (%s). Existing index columns: %s',
                $table,
                implode(', ', $columns),
                $indexColumns->map(static fn (array $indexedColumns): string => '(' . implode(', ', $indexedColumns) . ')')->implode(', ')
            )
        );
    }

    private function assertAppearsBefore(string $firstNeedle, string $secondNeedle, string $haystack): void
    {
        $firstPosition = mb_strpos($haystack, $firstNeedle);
        $secondPosition = mb_strpos($haystack, $secondNeedle);

        $this->assertNotFalse($firstPosition, sprintf('Could not find "%s" in the response.', $firstNeedle));
        $this->assertNotFalse($secondPosition, sprintf('Could not find "%s" in the response.', $secondNeedle));
        $this->assertLessThan($secondPosition, $firstPosition);
    }
}
