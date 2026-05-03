<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ActivityLogCleanupScheduleTest extends TestCase
{
    public function test_activity_log_cleanup_is_scheduled_daily_for_one_month_retention(): void
    {
        /** @var Event|null $event */
        $event = collect(resolve(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'activitylog:clean'));

        $this->assertNotNull($event);
        $this->assertStringContainsString('--days=30', (string) $event->command);
        $this->assertStringContainsString('--force', (string) $event->command);
        $this->assertSame('30 2 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(30, config('activitylog.clean_after_days'));
    }
}
