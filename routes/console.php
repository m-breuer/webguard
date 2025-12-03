<?php

use Illuminate\Support\Facades\Schedule;

/**
 * SITEMAP
 */
Schedule::command('sitemap:generate')->daily();

/**
 * NOTIFICATIONS
 */
Schedule::command('notifications:send-status-change-email')->everyMinute()->withoutOverlapping();
Schedule::command('notifications:delete-old-read')->daily();
Schedule::command('notifications:delete-old-guest')
    ->daily()
    ->withoutOverlapping();
Schedule::command('notifications:remind-unread')->dailyAt('06:00')->withoutOverlapping();

/**
 * MONITORINGS
 */
Schedule::command('monitoring:aggregate-daily-results')->daily();
Schedule::command('monitoring:archive-responses')->weekly();
Schedule::command('monitoring:check-ssl-expiry')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('monitoring:delete-soft-deleted')->monthly()->withoutOverlapping();
