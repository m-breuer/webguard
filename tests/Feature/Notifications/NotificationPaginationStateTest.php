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

    public function test_notifications_async_section_uses_default_limit_of_five_when_no_limit_is_present(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $notifications = $this->createSslExpiryNotifications($user, 7);

        $sortedByNewest = array_reverse($notifications);
        $visibleNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 0, 5));
        $hiddenNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 5));

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
        ]);

        $testResponse->assertOk();
        $testResponse->assertJsonPath('count', 5);
        $testResponse->assertJsonPath('hasMore', true);
        $html = (string) $testResponse->json('html');

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $this->assertStringContainsString('id="' . $visibleNotificationId . '"', $html);
        }

        foreach ($hiddenNotificationIds as $hiddenNotificationId) {
            $this->assertStringNotContainsString('id="' . $hiddenNotificationId . '"', $html);
        }
    }

    public function test_notifications_index_passes_limit_query_parameter_to_initial_async_load(): void
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
        $testResponse->assertSee('payload.limit = this.currentLimit');
        $testResponse->assertSeeHtml('window.history.replaceState');
        $testResponse->assertSeeHtml('syncLimitWithUrl(currentLimit)');
        $testResponse->assertSeeHtml('data-notification-filter-link');
        $testResponse->assertSeeHtml("document.querySelectorAll('[data-notification-filter-link]')");

        $sectionResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
            'limit' => 6,
        ]);

        $sectionResponse->assertOk();
        $sectionResponse->assertJsonPath('count', 6);
        $sectionResponse->assertJsonPath('hasMore', true);
        $html = (string) $sectionResponse->json('html');

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $this->assertStringContainsString('id="' . $visibleNotificationId . '"', $html);
        }

        foreach ($hiddenNotificationIds as $hiddenNotificationId) {
            $this->assertStringNotContainsString('id="' . $hiddenNotificationId . '"', $html);
        }
    }

    public function test_notifications_index_falls_back_to_default_async_limit_when_limit_query_parameter_is_invalid(): void
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

        $sectionResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
            'limit' => 5,
        ]);

        $sectionResponse->assertOk();
        $sectionResponse->assertJsonPath('count', 5);
        $sectionResponse->assertJsonPath('hasMore', true);
        $html = (string) $sectionResponse->json('html');

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $this->assertStringContainsString('id="' . $visibleNotificationId . '"', $html);
        }

        foreach ($hiddenNotificationIds as $hiddenNotificationId) {
            $this->assertStringNotContainsString('id="' . $hiddenNotificationId . '"', $html);
        }
    }

    public function test_notifications_async_section_rejects_limits_above_maximum(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
            'limit' => 101,
        ]);

        $testResponse->assertUnprocessable();
        $testResponse->assertJsonValidationErrors('limit');
    }

    public function test_notifications_async_section_accepts_maximum_limit_for_initial_load(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $notifications = $this->createSslExpiryNotifications($user, 101);

        $sortedByNewest = array_reverse($notifications);
        $visibleNotificationIds = array_map(fn (MonitoringNotification $monitoringNotification): string => $monitoringNotification->id, array_slice($sortedByNewest, 0, 100));
        $hiddenNotification = $sortedByNewest[100];

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
            'limit' => 100,
        ]);

        $testResponse->assertOk();
        $testResponse->assertJsonPath('count', 100);
        $testResponse->assertJsonPath('hasMore', true);
        $html = (string) $testResponse->json('html');

        foreach ($visibleNotificationIds as $visibleNotificationId) {
            $this->assertStringContainsString('id="' . $visibleNotificationId . '"', $html);
        }

        $this->assertStringNotContainsString('id="' . $hiddenNotification->id . '"', $html);
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

        $sectionResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
        ]);

        $sectionResponse->assertOk();
        $sectionResponse->assertJsonPath('count', 0);
    }

    public function test_notifications_navigation_uses_bell_icon_before_language_switch(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->get(route('monitorings.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('id="notifications-bell-desktop"');
        $testResponse->assertSeeHtml('id="notifications-bell-mobile"');
        $testResponse->assertSeeHtml('href="' . route('notifications.index') . '"');
        $testResponse->assertSeeHtml('aria-label="' . __('notifications.title') . '"');
        $testResponse->assertDontSeeHtml('>' . __('notifications.title') . '</a>');

        $content = $testResponse->getContent() ?? '';

        $this->assertLessThan(
            mb_strpos($content, 'id="language-switch-desktop"'),
            mb_strpos($content, 'id="notifications-bell-desktop"'),
        );
        $this->assertLessThan(
            mb_strpos($content, 'id="language-switch-mobile"'),
            mb_strpos($content, 'id="notifications-bell-mobile"'),
        );
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

    public function test_navigation_badge_counts_unread_status_changes_per_monitoring(): void
    {
        Date::setTestNow('2026-03-24 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'Unread SSL notification',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();
        $content = $testResponse->getContent() ?? '';

        preg_match_all('/bg-red-500[^>]*>(\d+)<\/span>/', $content, $matches);
        $badgeCounts = array_values(array_unique($matches[1] ?? []));

        $this->assertSame(['2'], $badgeCounts);
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
