<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Mail\UnreadNotificationsReminderMail;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class UnreadNotificationsReminderMailTest extends TestCase
{
    public function test_unread_notifications_reminder_uses_corporate_mail_design(): void
    {
        Package::factory()->create();

        $user = User::factory()->create([
            'name' => 'Mara Monitor',
        ]);

        $html = (new UnreadNotificationsReminderMail(7, $user))->render();

        $this->assertStringContainsString('font-family: Sen', $html);
        $this->assertStringContainsString('background-color: #f1f5f9', $html);
        $this->assertStringContainsString('class="mail-logo-mark"', $html);
        $this->assertStringContainsString(e(__('mail.general.brand_subtitle')), $html);
        $this->assertStringContainsString('class="mail-panel"', $html);
        $this->assertStringContainsString('class="mail-eyebrow"', $html);
        $this->assertStringContainsString('You have 7 unread notifications on the WebGuard platform.', $html);
        $this->assertStringNotContainsString('class="mail-card"', $html);
        $this->assertStringNotContainsString('class="mail-card-value">7</p>', $html);
        $this->assertStringNotContainsString('Unread notifications</p>', $html);
        $this->assertStringContainsString('class="mail-button"', $html);
        $this->assertStringContainsString(route('notifications.index'), $html);
        $this->assertStringContainsString(route('monitoring-locations'), $html);
        $this->assertStringContainsString(route('terms-of-use'), $html);
        $this->assertStringContainsString('Mara Monitor', $html);
    }
}
