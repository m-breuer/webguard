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
// Send status change notifications immediately.
Schedule::command('notifications:send-status-change-email')->everyMinute()->withoutOverlapping();

// Prune old read notifications daily.
Schedule::command('notifications:prune-read')->dailyAt('01:00');

// Prune old guest notifications daily.
Schedule::command('notifications:prune-guest')
    ->dailyAt('01:30')
    ->withoutOverlapping();

// Send daily reminders for unread notifications.
Schedule::command('notifications:remind-unread')->dailyAt('06:00')->withoutOverlapping();

/**
 * =================================================================
 * MONITORING & DATA MANAGEMENT
 * =================================================================
 */
// Aggregate raw monitoring data into daily summaries.
Schedule::command('monitoring:aggregate-daily')->dailyAt('00:30');

// Archive old monitoring responses weekly.
Schedule::command('monitoring:archive-responses')->weekly();

// Check for expiring SSL certificates daily.
Schedule::command('monitoring:check-ssl-expiry')->dailyAt('06:00')->withoutOverlapping();

// Permanently delete soft-deleted monitorings and their data monthly.
Schedule::command('monitoring:purge-soft-deleted')->monthly()->withoutOverlapping();
