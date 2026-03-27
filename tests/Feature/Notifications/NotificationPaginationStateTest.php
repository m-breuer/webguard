<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class NotificationPaginationStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_uses_default_limit_of_five_when_no_query_parameter_is_present(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $notifications = $this->createSslExpiryNotifications($user, 7);

        $sortedByNewest = array_reverse($notifications);
        $visibleNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 0, 5));
        $hiddenNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 5));

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $testResponse->assertSeeHtml('id="' . $visibleNotificationId . '"');
        }

        foreach ($hiddenNotificationIds as $hiddenNotificationId) {
            $testResponse->assertDontSeeHtml('id="' . $hiddenNotificationId . '"');
        }
    }

    public function test_notifications_index_uses_limit_query_parameter_for_initial_render_count(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $notifications = $this->createSslExpiryNotifications($user, 7);

        $sortedByNewest = array_reverse($notifications);
        $visibleNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 0, 6));
        $hiddenNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 6));

        $testResponse = $this->actingAs($user)->get(route('notifications.index', ['limit' => 6]));

        $testResponse->assertOk();
        $testResponse->assertSee('currentLimit: 6');

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $testResponse->assertSeeHtml('id="' . $visibleNotificationId . '"');
        }

        foreach ($hiddenNotificationIds as $hiddenNotificationId) {
            $testResponse->assertDontSeeHtml('id="' . $hiddenNotificationId . '"');
        }
    }

    public function test_notifications_index_falls_back_to_default_limit_when_limit_query_parameter_is_invalid(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $notifications = $this->createSslExpiryNotifications($user, 7);

        $sortedByNewest = array_reverse($notifications);
        $visibleNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 0, 5));
        $hiddenNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 5));

        $testResponse = $this->actingAs($user)->get(route('notifications.index', ['limit' => -10]));

        $testResponse->assertOk();
        $testResponse->assertSee('currentLimit: 5');

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $testResponse->assertSeeHtml('id="' . $visibleNotificationId . '"');
        }

        foreach ($hiddenNotificationIds as $hiddenNotificationId) {
            $testResponse->assertDontSeeHtml('id="' . $hiddenNotificationId . '"');
        }
    }

    public function test_notifications_page_contains_expected_empty_state_container_and_message(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL certificate will expire soon.',
            'read' => false,
            'sent' => false,
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));
        $testResponse->assertOk();
        $testResponse->assertSeeHtml('id="notifications-empty-state"');

        $markAsReadResponse = $this->actingAs($user)->post(route('notifications.markAsRead', $monitoringNotification->id));
        $markAsReadResponse->assertRedirect();

        $afterMarkResponse = $this->actingAs($user)->get(route('notifications.index'));
        $afterMarkResponse->assertOk();
        $afterMarkResponse->assertSee('Nothing to discover. Everything is up to date.');
    }

    public function test_navigation_badge_counts_only_unread_notifications(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'Unread notification',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'Read notification',
            'read' => true,
            'sent' => false,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index', ['show_read' => true]));

        $testResponse->assertOk();
        $content = $testResponse->getContent() ?? '';

        preg_match_all('/bg-red-500[^>]*>(\d+)<\/span>/', $content, $matches);
        $badgeCounts = array_values(array_unique($matches[1] ?? []));

        $this->assertSame(['1'], $badgeCounts);
    }

    /**
     * @return array<int, MonitoringNotification>
     */
    private function createSslExpiryNotifications(User $user, int $count): array
    {
        $monitoring = Monitoring::factory()->for($user)->create();
        $notifications = [];

        for ($minute = $count; $minute >= 1; $minute--) {
            $notifications[] = MonitoringNotification::query()->create([
                'monitoring_id' => $monitoring->id,
                'type' => NotificationType::SSL_EXPIRY,
                'message' => 'SSL certificate expires in ' . $minute . ' days.',
                'read' => false,
                'sent' => false,
                'created_at' => Date::now()->subMinutes($minute),
                'updated_at' => Date::now()->subMinutes($minute),
            ]);
        }

        return $notifications;
    }
}
