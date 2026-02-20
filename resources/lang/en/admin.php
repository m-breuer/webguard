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
    ],
    'server_instances' => [
        'title' => 'Server Instances',
        'fields' => [
            'code' => 'Instance Code',
            'api_key' => 'Instance API Key',
            'status' => 'Status',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'actions' => 'Actions',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
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
