<?php

declare(strict_types=1);

return [
    'title' => 'Admin',
    'dashboard' => [
        'heading' => 'Admin Dashboard',
        'users' => [
            'heading' => 'Manage Users',
            'description' => 'View, edit, and delete user accounts.',
        ],
        'packages' => [
            'heading' => 'Manage Packages',
            'description' => 'Control packages and access limits.',
        ],
        'apis' => [
            'heading' => 'Manage API Access',
            'description' => 'Generate keys, view logs, and manage usage.',
        ],
        'instances' => [
            'heading' => 'Manage Server Instances',
            'description' => 'Manage crawler instance codes and internal API keys.',
        ],
        'activity_logs' => [
            'heading' => 'Audit Logs',
            'description' => 'Review account, profile, API-token, and monitoring changes.',
        ],
        'infrastructure_health' => [
            'heading' => 'Infrastructure Health',
            'description' => 'Inspect scheduler, queue, cache, database, and scanner health.',
        ],
    ],
    'infrastructure_health' => [
        'title' => 'Infrastructure Health',
        'summary' => [
            'heading' => 'Application infrastructure status',
            'generated_at' => 'Generated at :timestamp',
        ],
        'statuses' => [
            'healthy' => 'Healthy',
            'warning' => 'Warning',
            'critical' => 'Critical',
        ],
        'checks' => [
            'database' => [
                'title' => 'Database',
                'healthy' => 'The :connection database connection is reachable.',
                'critical' => 'The :connection database connection failed.',
            ],
            'cache' => [
                'title' => 'Cache',
                'healthy' => 'The :store cache store accepted a read/write probe.',
                'critical' => 'The :store cache store failed a read/write probe.',
            ],
            'scheduler' => [
                'title' => 'Scheduler',
                'healthy' => 'The scheduler heartbeat was recorded :minutes_since_last_seen minutes ago.',
                'missing' => 'No scheduler heartbeat has been recorded yet.',
                'invalid' => 'The scheduler heartbeat value is invalid.',
                'stale' => 'The scheduler heartbeat is stale at :minutes_since_last_seen minutes old.',
                'critical' => 'The scheduler heartbeat could not be read from cache.',
            ],
            'queue' => [
                'title' => 'Queue',
                'healthy' => 'The :connection queue has :failed_jobs failed jobs.',
                'failed_jobs' => 'The :connection queue has :failed_jobs failed jobs.',
                'critical' => 'The failed job store could not be queried.',
            ],
            'scanner_instances' => [
                'title' => 'Scanner instances',
                'healthy' => 'All active scanner instances are reporting recently.',
                'degraded' => 'One or more active scanner instances are stale or have never reported.',
                'none_active' => 'No active scanner instances are configured.',
            ],
        ],
        'meta' => [
            'active_instances' => 'Active instances',
            'cache_key' => 'Cache key',
            'connection' => 'Connection',
            'empty' => 'n/a',
            'error' => 'Error',
            'failed_jobs' => 'Failed jobs',
            'healthy_instances' => 'Healthy instances',
            'inactive_instances' => 'Inactive instances',
            'last_seen_at' => 'Last seen',
            'minutes_since_last_seen' => 'Minutes since last seen',
            'never_seen_instances' => 'Never-seen instances',
            'stale_after_minutes' => 'Stale after minutes',
            'stale_instances' => 'Stale instances',
            'store' => 'Store',
            'threshold' => 'Threshold',
            'total_instances' => 'Total instances',
        ],
    ],
    'activity_logs' => [
        'title' => 'Audit Logs',
        'filters' => [
            'log_name' => 'Log',
            'event' => 'Event',
            'actor' => 'Actor',
            'subject_type' => 'Subject Type',
            'subject_id' => 'Subject ID',
            'date_from' => 'From',
            'date_to' => 'To',
            'apply' => 'Apply filters',
            'reset' => 'Reset',
        ],
        'fields' => [
            'created_at' => 'Date',
            'actor' => 'Actor',
            'log_name' => 'Log',
            'event' => 'Event',
            'subject' => 'Subject',
            'description' => 'Description',
            'changes' => 'Changes',
        ],
        'subject_types' => [
            'user' => 'User',
            'monitoring' => 'Monitoring',
        ],
        'messages' => [
            'empty' => 'No audit logs found.',
            'anonymous' => 'System / anonymous',
            'unknown_subject' => 'Unknown subject',
            'show_changes' => 'Show changes',
            'hide_changes' => 'Hide changes',
        ],
    ],
    'server_instances' => [
        'title' => 'Server Instances',
        'fields' => [
            'code' => 'Instance Code',
            'ip_address' => 'IPv4 Address',
            'api_key' => 'Instance API Key',
            'status' => 'Status',
            'health' => 'Health',
            'last_seen_at' => 'Last seen',
            'monitorings' => 'Monitorings',
            'monitoring_types' => 'Types',
            'never' => 'Never',
            'none' => 'None',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'actions' => 'Actions',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ],
        'summary' => [
            'total_instances' => 'Total instances',
            'active_instances' => 'Active instances',
            'stale_instances' => 'Stale instances',
            'total_monitorings' => 'Assigned monitorings',
        ],
        'monitorings_count' => ':count monitoring|:count monitorings',
        'health' => [
            'healthy' => 'Healthy',
            'stale' => 'Stale',
            'never_seen' => 'Never seen',
            'inactive' => 'Inactive',
        ],
        'messages' => [
            'confirm_delete' => 'Are you sure you want to delete this instance?',
            'no_instances' => 'No server instances found.',
            'instance_created' => 'Server instance created successfully.',
            'instance_updated' => 'Server instance updated successfully.',
            'instance_deleted' => 'Server instance deleted successfully.',
            'instance_in_use' => 'Server instance is in use and cannot be deleted.',
            'api_key_optional' => 'Leave empty to keep the current API key.',
        ],
        'create' => [
            'title' => 'Create Server Instance',
        ],
        'edit' => [
            'title' => 'Edit Server Instance',
        ],
    ],
    'packages' => [
        'title' => 'Packages',
        'fields' => [
            'monitoring_limit' => 'Monitoring Limit',
            'price' => 'Price',
            'is_selectable' => 'Selectable',
            'actions' => 'Actions',
            'yes' => 'Yes',
            'no' => 'No',
        ],
        'messages' => [
            'confirm_delete' => 'Are you sure you want to delete this package?',
            'no_packages' => 'No packages found.',
            'package_created' => 'Package created successfully.',
            'package_updated' => 'Package updated successfully.',
            'package_in_use' => 'Package is in use and cannot be deleted.',
            'package_deleted' => 'Package deleted successfully.',
        ],
        'create' => [
            'title' => 'Create Package',
        ],
        'edit' => [
            'title' => 'Edit Package',
        ],
    ],
];
