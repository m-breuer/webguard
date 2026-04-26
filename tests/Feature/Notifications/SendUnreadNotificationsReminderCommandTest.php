<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Mail\UnreadNotificationsReminderMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendUnreadNotificationsReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_daily_reminder_to_non_guest_users_with_unread_notifications(): void
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
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'UP',
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

    public function test_sends_reminder_to_guest_users_when_profile_setting_is_enabled(): void
    {
        Package::factory()->create();

        $guestUser = User::factory()->create([
            'role' => UserRole::GUEST,
            'unread_notifications_reminder_enabled' => true,
            'unread_notifications_reminder_frequency' => 'daily',
        ]);

        $monitoring = Monitoring::factory()->for($guestUser)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        Mail::fake();

        Artisan::call('notifications:remind-unread-weekly');

        Mail::assertSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($guestUser): bool {
            return $unreadNotificationsReminderMail->hasTo($guestUser->email)
                && $unreadNotificationsReminderMail->unreadNotificationsCount === 1;
        });
    }

    public function test_does_not_send_reminder_when_user_disabled_profile_setting(): void
    {
        Package::factory()->create();

        $user = User::factory()->create([
            'unread_notifications_reminder_enabled' => false,
            'unread_notifications_reminder_frequency' => 'daily',
        ]);

        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => true,
        ]);

        Mail::fake();

        Artisan::call('notifications:remind-unread-weekly');

        Mail::assertNotSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($user): bool {
            return $unreadNotificationsReminderMail->hasTo($user->email);
        });
    }

    public function test_respects_unread_reminder_frequency_settings(): void
    {
        Date::setTestNow('2026-04-07 08:00:00');

        try {
            Package::factory()->create();

            $dailyUser = User::factory()->create([
                'unread_notifications_reminder_enabled' => true,
                'unread_notifications_reminder_frequency' => 'daily',
            ]);
            $weeklyUser = User::factory()->create([
                'unread_notifications_reminder_enabled' => true,
                'unread_notifications_reminder_frequency' => 'weekly',
            ]);
            $monthlyUser = User::factory()->create([
                'unread_notifications_reminder_enabled' => true,
                'unread_notifications_reminder_frequency' => 'monthly',
            ]);

            foreach ([$dailyUser, $weeklyUser, $monthlyUser] as $user) {
                $monitoring = Monitoring::factory()->for($user)->create();

                MonitoringNotification::query()->create([
                    'monitoring_id' => $monitoring->id,
                    'type' => NotificationType::STATUS_CHANGE,
                    'message' => 'DOWN',
                    'read' => false,
                    'sent' => true,
                ]);
            }

            Mail::fake();

            Artisan::call('notifications:remind-unread-weekly');

            Mail::assertSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($dailyUser): bool {
                return $unreadNotificationsReminderMail->hasTo($dailyUser->email);
            });
            Mail::assertNotSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($weeklyUser): bool {
                return $unreadNotificationsReminderMail->hasTo($weeklyUser->email);
            });
            Mail::assertNotSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($monthlyUser): bool {
                return $unreadNotificationsReminderMail->hasTo($monthlyUser->email);
            });
        } finally {
            Date::setTestNow();
        }
    }

    public function test_weekly_and_monthly_reminders_are_sent_when_due(): void
    {
        Date::setTestNow('2026-06-01 08:00:00');

        try {
            Package::factory()->create();

            $weeklyUser = User::factory()->create([
                'unread_notifications_reminder_enabled' => true,
                'unread_notifications_reminder_frequency' => 'weekly',
            ]);
            $monthlyUser = User::factory()->create([
                'unread_notifications_reminder_enabled' => true,
                'unread_notifications_reminder_frequency' => 'monthly',
            ]);

            foreach ([$weeklyUser, $monthlyUser] as $user) {
                $monitoring = Monitoring::factory()->for($user)->create();

                MonitoringNotification::query()->create([
                    'monitoring_id' => $monitoring->id,
                    'type' => NotificationType::STATUS_CHANGE,
                    'message' => 'DOWN',
                    'read' => false,
                    'sent' => true,
                ]);
            }

            Mail::fake();

            Artisan::call('notifications:remind-unread-weekly');

            Mail::assertSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($weeklyUser): bool {
                return $unreadNotificationsReminderMail->hasTo($weeklyUser->email);
            });
            Mail::assertSent(UnreadNotificationsReminderMail::class, function (UnreadNotificationsReminderMail $unreadNotificationsReminderMail) use ($monthlyUser): bool {
                return $unreadNotificationsReminderMail->hasTo($monthlyUser->email);
            });
        } finally {
            Date::setTestNow();
        }
    }

    public function test_does_not_send_reminder_when_no_unread_notifications_exist(): void
    {
        Mail::fake();

        Artisan::call('notifications:remind-unread-weekly');

        Mail::assertNothingSent();
    }
}
