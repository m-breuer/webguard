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
];
