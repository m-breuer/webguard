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
    'incident_review' => [
        'heading' => 'Internal incident review',
        'description' => 'Record what caused the incident and what fixed it. These notes stay private and are not shown on the public status page.',
        'problem' => 'What was the problem?',
        'problem_placeholder' => 'Describe the root cause, contributing factors, or customer impact.',
        'resolution' => 'What fixed it?',
        'resolution_placeholder' => 'Describe the mitigation, fix, or recovery steps.',
        'save' => 'Save review notes',
        'messages' => [
            'updated' => 'Incident review notes updated successfully.',
        ],
    ],
    'public' => [
        'title' => ':statusPage - Status',
        'overall_status' => 'Overall status',
        'recent_incidents' => 'Recent Incidents',
        'subscribe' => [
            'button' => 'Subscribe',
            'confirmation_sent' => 'If needed, we sent a confirmation email to finish the subscription.',
            'confirmed' => 'Your status page update subscription is active.',
            'description' => 'Get incident and recovery updates for this status page by email.',
            'email' => 'Email address',
            'email_placeholder' => 'you@example.com',
            'heading' => 'Subscribe to updates',
            'unsubscribe_button' => 'Unsubscribe',
            'unsubscribe_confirmation' => 'Are you sure you want to unsubscribe from these status page updates?',
            'unsubscribe_description' => 'Stop sending status page updates to :email.',
            'unsubscribe_heading' => 'Unsubscribe from status page updates',
            'unsubscribe_title' => 'Unsubscribe from :statusPageName updates',
            'unsubscribed' => 'You have been unsubscribed from status page updates.',
        ],
    ],
    'validation' => [
        'fix_errors' => 'Please fix the highlighted status page settings.',
        'monitoring_not_accessible' => 'The selected monitoring is not accessible.',
    ],
];
