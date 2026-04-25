<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/**
 * =================================================================
 * SITEMAP
 * =================================================================
 */
// Generate the sitemap daily.
Schedule::command('sitemap:generate')->dailyAt('02:00');

/**
 * =================================================================
 * NOTIFICATIONS
 * =================================================================
 */
// Dispatch status change notifications immediately.
Schedule::command('notifications:dispatch-status-changes')->everyMinute()->withoutOverlapping();

// Remind users daily about unread notifications in their board.
Schedule::command('notifications:remind-unread-weekly')->dailyAt('08:00')->withoutOverlapping();

// Send customers a weekly monitoring performance digest.
Schedule::command('notifications:send-weekly-monitoring-digest')->weeklyOn(1, '08:30')->withoutOverlapping();

// Prune old read notifications daily.
Schedule::command('notifications:prune-read')->dailyAt('01:00');

// Prune old guest notifications daily.
Schedule::command('notifications:prune-guest')
    ->dailyAt('01:30')
    ->withoutOverlapping();

/**
 * =================================================================
 * MONITORING & DATA MANAGEMENT
 * =================================================================
 */
// Aggregate raw monitoring data into daily summaries.
Schedule::command('monitoring:aggregate-daily')->dailyAt('00:30');

// Evaluate passive heartbeat monitorings for missed pings.
Schedule::command('monitoring:evaluate-heartbeats')->everyMinute()->withoutOverlapping();

// Archive old monitoring responses weekly.
Schedule::command('monitoring:archive-responses')->weekly();

// Check for expiring SSL certificates daily.
Schedule::command('notifications:send-ssl-expiry-warnings')->dailyAt('06:00')->withoutOverlapping();

// Permanently delete soft-deleted monitorings and their data monthly.
Schedule::command('monitoring:purge-soft-deleted')->monthly()->withoutOverlapping();
