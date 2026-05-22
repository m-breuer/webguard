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
        $testResponse->assertSeeText(__('notifications.overview.eyebrow'));
        $testResponse->assertSeeText(__('notifications.overview.description'));
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

        $notification = MonitoringNotification::query()->create([
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
        $this->assertStringContainsString('aria-label="' . __('notifications.mark_as_read') . '"', $html);
        $this->assertStringContainsString('id="' . $notification->id . '"', $html);
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

        $statusNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
        ]);

        $delivery = NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $user->id,
            'monitoring_notification_id' => $statusNotification->id,
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

        $statusResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => NotificationType::STATUS_CHANGE->value,
            'offset' => 0,
        ]);
        $deliveryResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => 'delivery_history',
            'offset' => 0,
        ]);

        $statusResponse->assertOk();
        $deliveryResponse->assertOk();

        $statusHtml = (string) $statusResponse->json('html');
        $deliveryHtml = (string) $deliveryResponse->json('html');

        $this->assertStringContainsString('data-notification-card="status_change"', $statusHtml);
        $this->assertStringContainsString('notification-card-accent', $statusHtml);
        $this->assertStringContainsString('id="' . $statusNotification->id . '"', $statusHtml);

        $this->assertStringContainsString('data-notification-card="delivery_history"', $deliveryHtml);
        $this->assertStringContainsString('notification-card-accent', $deliveryHtml);
        $this->assertStringContainsString('id="' . $delivery->id . '"', $deliveryHtml);
        $this->assertStringContainsString('Webhook responded with HTTP 500.', $deliveryHtml);
    }
}
