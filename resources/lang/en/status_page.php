<?php

declare(strict_types=1);

return [
    'title' => 'Status Pages',
    'components_count' => ':count component|:count components',
    'state' => [
        'public' => 'Public',
        'private' => 'Private',
    ],
    'empty' => [
        'title' => 'No status pages yet',
        'text' => 'Group monitorings into customer-facing components such as API, Web App, Workers, or Database.',
    ],
    'create' => [
        'title' => 'Create Status Page',
    ],
    'edit' => [
        'title' => 'Edit :statusPage',
    ],
    'form' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'slug_placeholder' => 'Generated from the name when empty',
        'description' => 'Description',
        'is_public' => 'Publish this status page',
        'components' => 'Components',
        'component_name' => 'Component name',
        'component_description' => 'Component description',
        'monitorings' => 'Monitorings',
        'monitoring_group' => 'Monitoring group',
        'monitoring_group_source_help' => 'This component dynamically uses the monitorings from the monitoring group.',
        'add_component' => 'Add component',
        'remove_component' => 'Remove component',
    ],
    'actions' => [
        'public_page' => 'Public page',
        'delete_confirmation' => 'Are you sure you want to delete this status page?',
    ],
    'messages' => [
        'created' => 'Status page created successfully.',
        'updated' => 'Status page updated successfully.',
        'deleted' => 'Status page deleted successfully.',
    ],
    'incident_updates' => [
        'heading' => 'Incident Updates',
        'description' => 'Post manual updates so visitors can follow the incident timeline.',
        'status' => 'Status',
        'message' => 'Update message',
        'add' => 'Add update',
        'statuses' => [
            'investigating' => 'Investigating',
            'identified' => 'Identified',
            'monitoring' => 'Monitoring',
            'resolved' => 'Resolved',
        ],
        'messages' => [
            'created' => 'Incident update added successfully.',
        ],
    ],
    'public' => [
        'title' => ':statusPage - Status',
        'overall_status' => 'Overall status',
        'recent_incidents' => 'Recent Incidents',
    ],
    'validation' => [
        'fix_errors' => 'Please fix the highlighted status page settings.',
        'monitoring_not_accessible' => 'The selected monitoring is not accessible.',
    ],
];
