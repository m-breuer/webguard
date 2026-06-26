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
        'demo_monitorings' => [
            'heading' => 'Manage Demo Monitorings',
            'description' => 'View, create, edit, and delete monitorings for the demo user.',
        ],
        'activity_logs' => [
            'heading' => 'Audit Logs',
            'description' => 'Review account, profile, API-token, and monitoring changes.',
        ],
    ],
    'demo_monitorings' => [
        'title' => 'Demo User Monitorings',
        'actions' => [
            'create' => 'Add Demo Monitoring',
        ],
        'fields' => [
            'created_at' => 'Created',
            'actions' => 'Actions',
        ],
        'summary' => [
            'demo_user' => 'Demo User',
            'monitorings' => 'Monitorings',
            'package_limit' => 'Package Limit',
        ],
        'messages' => [
            'empty' => 'No demo user monitorings found.',
            'confirm_delete' => 'Are you sure you want to delete this demo user monitoring?',
            'created' => 'Demo user monitoring created successfully.',
            'updated' => 'Demo user monitoring updated successfully.',
            'deleted' => 'Demo user monitoring deleted successfully.',
        ],
        'create' => [
            'title' => 'Create Demo User Monitoring',
            'demo_user' => 'Monitoring owner',
        ],
        'edit' => [
            'title' => 'Edit :monitoring',
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
            'stale_instances' => 'Unreachable instances',
            'total_monitorings' => 'Assigned monitorings',
        ],
        'monitorings_count' => ':count monitoring|:count monitorings',
        'health' => [
            'healthy' => 'Reachable',
            'stale' => 'Unreachable',
            'never_seen' => 'No report yet',
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
