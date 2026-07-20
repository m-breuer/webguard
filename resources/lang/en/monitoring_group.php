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
        'monitorings' => 'Monitorings',
        'no_monitorings' => 'No monitorings selected',
        'search_monitorings' => 'Search monitorings by name or target...',
        'select_all_monitorings' => 'Select all',
        'all_monitorings_selected' => 'All matching monitorings are selected.',
        'no_monitorings_available' => 'No private monitorings are available.',
        'no_monitorings_found' => 'No matching monitorings found.',
        'remove_monitoring' => 'Remove monitoring:',
        'clear_monitorings' => 'Remove all monitorings',
        'monitorings_help' => 'Optionally assign monitorings while saving this group.',
    ],
    'validation' => [
        'monitoring_not_manageable' => 'The selected monitoring cannot be assigned to your group.',
    ],
    'filter' => [
        'all' => 'All groups',
        'label' => 'Group',
    ],
    'monitorings_count' => ':count monitoring|:count monitorings',
    'actions' => [
        'publish_status_page' => 'Publish as status page',
        'delete' => [
            'confirmation' => 'Are you sure you want to delete this group? Monitorings remain available and only lose this group assignment.',
        ],
    ],
    'messages' => [
        'created' => 'Group created successfully.',
        'updated' => 'Group updated successfully.',
        'deleted' => 'Group deleted successfully.',
        'status_page_created' => 'Status page for this group created successfully.',
    ],
];
