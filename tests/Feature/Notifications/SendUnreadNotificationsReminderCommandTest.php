<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Mail\UnreadNotificationsReminderMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendUnreadNotificationsReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_weekly_reminder_to_users_with_unread_notifications(): void
    {
        Package::factory()->create();

        $userWithUnread = User::factory()->create();
        $userWithoutUnread = User::factory()->create();

        $monitoringWithUnread = Monitoring::factory()->for($userWithUnread)->create();
        $monitoringWithoutUnread = Monitoring::factory()->for($userWithoutUnread)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoringWithUnread->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoringWithUnread->id,
            'type' => NotificationType::SSL_EXPIRY,
            'message' => 'SSL_EXPIRING',
            'read' => false,
            'sent' => true,
        ]);

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoringWithoutUnread->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
            'read' => true,
            'sent' => true,
        ]);

        Mail::fake();

        Artisan::call('notifications:remind-unread-weekly');

        Mail::assertSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($userWithUnread): bool {
            return $unreadNotificationsReminderMail->hasTo($userWithUnread->email)
                && $unreadNotificationsReminderMail->unreadNotificationsCount === 2;
        });
        Mail::assertNotSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($userWithoutUnread): bool {
            return $unreadNotificationsReminderMail->hasTo($userWithoutUnread->email);
        });
    }

    public function test_does_not_send_reminder_when_no_unread_notifications_exist(): void
    {
        Mail::fake();

        Artisan::call('notifications:remind-unread-weekly');

        Mail::assertNothingSent();
    }
}
