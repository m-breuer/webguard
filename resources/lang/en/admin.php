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
    ],
    'server_instances' => [
        'title' => 'Server Instances',
        'list' => 'List of Server Instances',
        'link_to_instance' => 'Go to Instance',
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
