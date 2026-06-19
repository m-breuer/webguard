<?php

declare(strict_types=1);

return [
    'title' => 'Groups',
    'create' => [
        'title' => 'Create Group',
    ],
    'edit' => [
        'title' => 'Edit :group',
    ],
    'empty' => [
        'title' => 'No groups yet',
        'text' => 'Create groups to organize and filter your monitorings.',
    ],
    'form' => [
        'name' => 'Name',
        'description' => 'Description',
        'public_label_enabled' => 'Enable public group label',
        'public_label_help' => 'The public group label lists only monitorings in this group that also have their own public label enabled.',
    ],
    'filter' => [
        'all' => 'All groups',
        'label' => 'Group',
    ],
    'state' => [
        'private' => 'Private',
        'public' => 'Public',
    ],
    'monitorings_count' => ':count monitoring|:count monitorings',
    'actions' => [
        'public_label' => 'Public label',
        'delete' => [
            'confirmation' => 'Are you sure you want to delete this group? Monitorings remain available and only lose this group assignment.',
        ],
    ],
    'messages' => [
        'created' => 'Group created successfully.',
        'updated' => 'Group updated successfully.',
        'deleted' => 'Group deleted successfully.',
    ],
    'public_label' => [
        'title' => ':groupName - Public Status',
        'empty' => [
            'title' => 'No public monitorings',
            'text' => 'This group does not contain any monitorings with public labels enabled.',
        ],
    ],
];
