<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DispatchStatusChangeNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_status_change_to_enabled_user_channels(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.test/services/test',
                    'events' => [
                        'incident' => true,
                        'recovery' => true,
                    ],
                ],
            ],
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'notification_on_failure' => true,
        ]);

        $notification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
        ]);

        Http::fake([
            'https://hooks.slack.test/*' => Http::response(['ok' => true], 200),
        ]);

        Artisan::call('notifications:dispatch-status-changes');

        $notification->refresh();
        $this->assertTrue($notification->sent);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://hooks.slack.test/services/test'
                && data_get($request->data(), 'payload.event_type') === 'incident';
        });
    }

    public function test_command_respects_per_monitoring_notification_flag(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'notification_channels' => [
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.test/services/test',
                    'events' => [
                        'incident' => true,
                        'recovery' => true,
                    ],
                ],
            ],
        ]);

        $monitoring = Monitoring::factory()->for($user)->create([
            'notification_on_failure' => false,
        ]);

        $notification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
        ]);

        Http::fake();

        Artisan::call('notifications:dispatch-status-changes');

        $notification->refresh();
        $this->assertTrue($notification->sent);
        Http::assertNothingSent();
    }
}

