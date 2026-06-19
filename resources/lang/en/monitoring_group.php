<?php

declare(strict_types=1);

return [
    'title' => 'Monitoring Groups',
    'create' => [
        'title' => 'Create Monitoring Group',
    ],
    'edit' => [
        'title' => 'Edit :group',
    ],
    'empty' => [
        'title' => 'No monitoring groups yet',
        'text' => 'Create groups to organize and filter your monitorings.',
    ],
    'form' => [
        'name' => 'Name',
        'description' => 'Description',
    ],
    'filter' => [
        'all' => 'All groups',
        'label' => 'Monitoring group',
    ],
    'monitorings_count' => ':count monitoring|:count monitorings',
    'actions' => [
        'publish_status_page' => 'Publish as status page',
        'delete' => [
            'confirmation' => 'Are you sure you want to delete this monitoring group? Monitorings remain available and only lose this group assignment.',
        ],
    ],
    'messages' => [
        'created' => 'Monitoring group created successfully.',
        'updated' => 'Monitoring group updated successfully.',
        'deleted' => 'Monitoring group deleted successfully.',
        'status_page_created' => 'Status page for this monitoring group created successfully.',
    ],
];
