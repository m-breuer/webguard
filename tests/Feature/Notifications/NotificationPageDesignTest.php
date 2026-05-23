<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\MonitoringStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\NotificationChannelDelivery;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class NotificationPageDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_renders_professional_command_center_shell(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('id="notification-command-center"');
        $testResponse->assertSeeHtml('id="notification-action-panel"');
        $testResponse->assertDontSeeHtml('M15 17h5');
        $testResponse->assertSeeText(__('notifications.overview.eyebrow'));
        $testResponse->assertSeeText(__('notifications.overview.description'));
        $testResponse->assertSeeText(__('notifications.filters.heading'));
        $testResponse->assertSeeText(__('notifications.filters.unread'));
        $testResponse->assertSeeText(__('notifications.filters.all'));
        $testResponse->assertSeeText(__('notifications.mark_all_as_read'));
        $testResponse->assertSeeHtml('bg-emerald-500 text-white');
        $testResponse->assertSeeHtml('dark:bg-emerald-400 dark:text-slate-950');
        $testResponse->assertSeeHtml('dark:!bg-emerald-400 dark:!text-slate-950');
        $testResponse->assertSeeHtml('sm:grid-cols-[1fr_auto]');
        $testResponse->assertSeeHtml('sm:grid-cols-3');
        $testResponse->assertSeeText(__('notifications.loading.title'));
        $testResponse->assertSeeText(__('notifications.empty_state.description'));
        $testResponse->assertSeeHtml('data-notification-section="ssl_expiry"');
        $testResponse->assertSeeHtml('data-notification-section="domain_expiry"');
        $testResponse->assertSeeHtml('data-notification-section="status_change"');
        $testResponse->assertSeeHtml('data-notification-section="delivery_history"');
    }

    public function test_async_notification_cards_use_redesigned_card_markup(): void
    {
        Date::setTestNow('2026-05-22 10:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => false,
        ]);

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::SSL_EXPIRY->value,
            'offset' => 0,
        ]);

        $testResponse->assertOk();
        $html = (string) $testResponse->json('html');

        $this->assertStringContainsString('data-notification-card="ssl_expiry"', $html);
        $this->assertStringContainsString('notification-card-accent', $html);
        $this->assertStringNotContainsString('inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1', $html);
        $this->assertStringContainsString('aria-label="' . __('notifications.mark_as_read') . '"', $html);
        $this->assertStringContainsString('!bg-emerald-500', $html);
        $this->assertStringContainsString('dark:!text-slate-950', $html);
        $this->assertStringContainsString('id="' . $monitoringNotification->id . '"', $html);
        $this->assertStringContainsString('Checkout API', $html);
    }

    public function test_status_board_and_delivery_history_cards_use_redesigned_card_markup(): void
    {
        Date::setTestNow('2026-05-22 10:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'target' => 'https://primary.example.test',
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => 1200.0,
        ]);

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
        ]);

        $notificationChannelDelivery = NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $user->id,
            'monitoring_notification_id' => $monitoringNotification->id,
            'channel' => 'slack',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::FAILED->value,
            'payload' => [
                'monitoring' => [
                    'name' => $monitoring->name,
                    'target' => $monitoring->target,
                ],
            ],
            'error_message' => 'Webhook responded with HTTP 500.',
        ]);

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::STATUS_CHANGE->value,
            'offset' => 0,
        ]);
        $deliveryResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => 'delivery_history',
            'offset' => 0,
        ]);

        $testResponse->assertOk();
        $deliveryResponse->assertOk();

        $statusHtml = (string) $testResponse->json('html');
        $deliveryHtml = (string) $deliveryResponse->json('html');

        $this->assertStringContainsString('data-notification-card="status_change"', $statusHtml);
        $this->assertStringContainsString('notification-card-accent', $statusHtml);
        $this->assertStringNotContainsString('inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg', $statusHtml);
        $this->assertStringContainsString('id="' . $monitoringNotification->id . '"', $statusHtml);

        $this->assertStringContainsString('data-notification-card="delivery_history"', $deliveryHtml);
        $this->assertStringContainsString('notification-card-accent', $deliveryHtml);
        $this->assertStringContainsString('id="' . $notificationChannelDelivery->id . '"', $deliveryHtml);
        $this->assertStringContainsString('Webhook responded with HTTP 500.', $deliveryHtml);
    }

    public function test_read_notification_cards_show_read_state_without_read_actions(): void
    {
        Date::setTestNow('2026-05-22 10:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Billing API',
            'target' => 'https://billing.example.test',
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 180.0,
        ]);

        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::DOMAIN_EXPIRY,
            'message' => 'DOMAIN_EXPIRING',
            'read' => true,
            'sent' => false,
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);

        $statusNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => true,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
        ]);

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::DOMAIN_EXPIRY->value,
            'offset' => 0,
            'show_read' => true,
        ]);
        $statusResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::STATUS_CHANGE->value,
            'offset' => 0,
            'show_read' => true,
        ]);

        $testResponse->assertOk();
        $statusResponse->assertOk();

        $domainHtml = (string) $testResponse->json('html');
        $statusHtml = (string) $statusResponse->json('html');

        $this->assertStringContainsString('data-notification-card="domain_expiry"', $domainHtml);
        $this->assertStringContainsString('id="' . $monitoringNotification->id . '"', $domainHtml);
        $this->assertStringContainsString('opacity-70', $domainHtml);
        $this->assertStringContainsString(__('notifications.read'), $domainHtml);
        $this->assertStringNotContainsString('mark-as-read-button', $domainHtml);
        $this->assertStringNotContainsString('aria-label="' . __('notifications.mark_as_read') . '"', $domainHtml);

        $this->assertStringContainsString('data-notification-card="status_change"', $statusHtml);
        $this->assertStringContainsString('id="' . $statusNotification->id . '"', $statusHtml);
        $this->assertStringContainsString('opacity-70', $statusHtml);
        $this->assertStringContainsString(__('notifications.read'), $statusHtml);
        $this->assertStringNotContainsString('mark-as-read-button', $statusHtml);
        $this->assertStringNotContainsString('aria-label="' . __('notifications.mark_as_read') . '"', $statusHtml);
    }
}
