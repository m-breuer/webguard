<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Interval
    |--------------------------------------------------------------------------
    |
    | This value determines the interval in minutes at which the monitoring
    | checks are performed. The default value is 5 minutes. This value
    | is used throughout the application to ensure consistency.
    |
    */
    'interval' => 5,

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Queue Name
    |--------------------------------------------------------------------------
    |
    | Missed heartbeat evaluation is dispatched onto a dedicated queue so it
    | can be processed separately from the default application workload while
    | still using the standard Redis queue connection.
    |
    */
    'heartbeat_queue' => env('HEARTBEAT_QUEUE', 'heartbeat'),

    /*
    |--------------------------------------------------------------------------
    | Server Instance Health
    |--------------------------------------------------------------------------
    |
    | Internal scanner instances update their last-seen timestamp after a
    | successful API authentication. Writes are throttled to avoid updating on
    | every polling request, while stale_after_minutes controls the admin health
    | indicator for active instances.
    |
    */
    'instance_seen_write_throttle_seconds' => (int) env('MONITORING_INSTANCE_SEEN_WRITE_THROTTLE_SECONDS', 60),
    'instance_stale_after_minutes' => (int) env('MONITORING_INSTANCE_STALE_AFTER_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Weekly Digest
    |--------------------------------------------------------------------------
    |
    | SSL certificates and domains expiring within this window are included in
    | the weekly monitoring digest so customers can act before critical expiry.
    |
    */
    'digest_expiry_warning_days' => (int) env('MONITORING_DIGEST_EXPIRY_WARNING_DAYS', 30),
];
