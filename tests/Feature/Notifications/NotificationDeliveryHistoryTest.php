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
use Tests\TestCase;

class NotificationDeliveryHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_async_delivery_history_shows_authenticated_user_entries_only(): void
    {
        Date::setTestNow('2026-04-06 10:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'target' => 'https://status.example.test',
        ]);

        $monitoringNotification = MonitoringNotification::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
            'created_at' => Date::now()->subMinute(),
            'updated_at' => Date::now()->subMinute(),
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
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ]);

        $payloadOnlyDelivery = NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $user->id,
            'monitoring_notification_id' => null,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::SSL_EXPIRED->value,
            'status' => NotificationDeliveryStatus::SENT->value,
            'payload' => [
                'monitoring' => [
                    'name' => 'Worker API',
                    'target' => 'https://worker.example.test',
                ],
            ],
            'sent_at' => Date::now()->subSeconds(30),
            'created_at' => Date::now()->subSeconds(30),
            'updated_at' => Date::now()->subSeconds(30),
        ]);

        $teamsDelivery = NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $user->id,
            'monitoring_notification_id' => null,
            'channel' => 'teams',
            'event_type' => NotificationEventType::RECOVERY->value,
            'status' => NotificationDeliveryStatus::SENT->value,
            'payload' => [
                'monitoring' => [
                    'name' => 'Teams API',
                    'target' => 'https://teams.example.test',
                ],
            ],
            'sent_at' => Date::now()->subSeconds(45),
            'created_at' => Date::now()->subSeconds(45),
            'updated_at' => Date::now()->subSeconds(45),
        ]);

        $hiddenDelivery = NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $otherUser->id,
            'monitoring_notification_id' => null,
            'channel' => 'discord',
            'event_type' => NotificationEventType::RECOVERY->value,
            'status' => NotificationDeliveryStatus::SENT->value,
            'payload' => [
                'monitoring' => [
                    'name' => 'Foreign Monitoring',
                    'target' => 'https://other.example.test',
                ],
            ],
            'sent_at' => Date::now()->subMinutes(2),
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('notifications.delivery_history.heading'));
        $testResponse->assertDontSeeText('Primary API');
        $testResponse->assertSee('loadInitialNotifications()');

        $sectionResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => 'delivery_history',
            'offset' => 0,
        ]);

        $sectionResponse->assertOk();
        $html = (string) $sectionResponse->json('html');

        $this->assertStringContainsString('id="' . $notificationChannelDelivery->id . '"', $html);
        $this->assertStringContainsString('id="' . $payloadOnlyDelivery->id . '"', $html);
        $this->assertStringContainsString('id="' . $teamsDelivery->id . '"', $html);
        $this->assertStringNotContainsString('id="' . $hiddenDelivery->id . '"', $html);
        $this->assertStringContainsString('Primary API', $html);
        $this->assertStringContainsString('Worker API', $html);
        $this->assertStringContainsString('Teams API', $html);
        $this->assertStringContainsString(__('notifications.channels.slack'), $html);
        $this->assertStringContainsString(__('notifications.channels.teams'), $html);
        $this->assertStringContainsString(__('notifications.events.incident'), $html);
        $this->assertStringContainsString(__('notifications.delivery_status.failed'), $html);
        $this->assertStringContainsString('Webhook responded with HTTP 500.', $html);
    }

    public function test_notifications_load_more_returns_additional_delivery_history_entries(): void
    {
        Date::setTestNow('2026-04-06 10:00:00');

        Package::factory()->create();
        $user = User::factory()->create();

        $deliveries = [];

        for ($minute = 6; $minute >= 1; $minute--) {
            $deliveries[] = NotificationChannelDelivery::query()->forceCreate([
                'user_id' => $user->id,
                'monitoring_notification_id' => null,
                'channel' => 'telegram',
                'event_type' => NotificationEventType::INCIDENT->value,
                'status' => NotificationDeliveryStatus::SENT->value,
                'payload' => [
                    'monitoring' => [
                        'name' => 'Delivery ' . $minute,
                        'target' => 'https://delivery-' . $minute . '.example.test',
                    ],
                ],
                'sent_at' => Date::now()->subMinutes($minute),
                'created_at' => Date::now()->subMinutes($minute),
                'updated_at' => Date::now()->subMinutes($minute),
            ]);
        }

        $testResponse = $this->actingAs($user)->postJson(route('notifications.loadMore'), [
            'type' => 'delivery_history',
            'offset' => 5,
        ]);

        $testResponse->assertOk();
        $testResponse->assertJsonPath('count', 1);
        $testResponse->assertJsonPath('hasMore', false);
        $this->assertStringContainsString('Delivery 6', (string) $testResponse->json('html'));
    }

    public function test_notifications_index_uses_delivery_history_offset_for_delivery_history_load_more_requests(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        NotificationChannelDelivery::query()->forceCreate([
            'user_id' => $user->id,
            'monitoring_notification_id' => null,
            'channel' => 'webhook',
            'event_type' => NotificationEventType::INCIDENT->value,
            'status' => NotificationDeliveryStatus::SENT->value,
            'payload' => [
                'monitoring' => [
                    'name' => 'Delivery offset check',
                    'target' => 'https://delivery-offset.example.test',
                ],
            ],
        ]);

        $testResponse = $this->actingAs($user)->get(route('notifications.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('getOffsetForType(type)');
        $testResponse->assertSeeHtml("if (type === 'delivery_history') {");
        $testResponse->assertSeeHtml('return this.deliveryHistoryOffset;');
        $testResponse->assertSeeHtml('const offset = initial ? 0 : this.getOffsetForType(type);');
        $testResponse->assertSeeHtml("loadMoreNotifications('delivery_history')");
    }
}
