<?php

declare(strict_types=1);

return [
    'fields' => [
        'placeholder_generic' => 'Search',
        'placeholder' => 'Search by :attribute',
        'placeholder_monitoring' => 'Search by name, target, port or keyword',
    ],
    'actions' => [
        'search' => 'Search',
    ],
    'messages' => [
        'error' => 'No results found. Please try again later.',
        'loading' => 'Loading ...',
    ],
    'filter' => [
        'heading' => 'Filter',
        'text' => 'Filter by :attribute',
        'all' => 'All',
        'name' => [
            'asc' => 'Name (A-Z)',
            'desc' => 'Name (Z-A)',
        ],
        'date' => [
            'asc' => 'Oldest',
            'desc' => 'Latest',
        ],
        'status' => [
            'active' => 'Active',
            'paused' => 'Paused',
        ],
        'lifecycle' => 'Lifecycle status',
    ],
    'table' => [
        'per_page' => 'Entries per page',
        'showing' => 'Showing',
        'to' => 'to',
        'of' => 'of',
        'entries' => 'entries',
        'pagination' => 'Pagination',
    ],
];
