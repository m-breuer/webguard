<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/**
 * SITEMAP
 */
Schedule::command('sitemap:generate')->daily();

/**
 * NOTIFICATIONS
 */
Schedule::command('notifications:send-status-change-email')->everyMinute()->withoutOverlapping();
Schedule::command('notifications:prune-read')->daily();
Schedule::command('notifications:prune-guest')
    ->daily()
    ->withoutOverlapping();
Schedule::command('notifications:remind-unread')->dailyAt('06:00')->withoutOverlapping();

/**
 * MONITORINGS
 */
Schedule::command('monitoring:aggregate-daily')->daily();
Schedule::command('monitoring:archive-responses')->weekly();
Schedule::command('monitoring:check-ssl-expiry')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('monitoring:purge-soft-deleted')->monthly()->withoutOverlapping();
