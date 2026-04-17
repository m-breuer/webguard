<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class NotificationsScheduleTest extends TestCase
{
    public function test_unread_notifications_reminder_is_scheduled_daily_at_eight_am(): void
    {
        /** @var Event|null $event */
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'notifications:remind-unread-weekly'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
