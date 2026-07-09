<?php

declare(strict_types=1);

return [
    'title' => 'Maintenance',
    'schedule' => [
        'heading' => 'Schedule maintenance',
        'description' => 'Plan a maintenance window for one monitoring or apply the same window to every monitoring in a group.',
    ],
    'form' => [
        'scope' => 'Apply to',
        'scopes' => [
            'monitoring' => 'Single monitoring',
            'group' => 'Monitoring group',
        ],
        'monitoring' => 'Monitoring',
        'group' => 'Group',
        'select_monitoring' => 'Select monitoring',
        'select_group' => 'Select group',
        'from' => 'Maintenance from',
        'until' => 'Maintenance until',
        'help' => 'During the maintenance window, checks are skipped and the status is reported as UNKNOWN. Leave the end empty for an open-ended window.',
    ],
    'windows' => [
        'heading' => 'Planned windows',
        'description' => 'Current and upcoming windows across your monitorings.',
    ],
    'summary' => [
        'total' => 'Total',
    ],
    'table' => [
        'groups' => 'Groups',
        'actions' => 'Actions',
        'status_filter' => 'Maintenance status',
        'group_filter' => 'Monitoring group',
    ],
    'status' => [
        'active' => 'Active',
        'upcoming' => 'Upcoming',
        'expired' => 'Expired',
        'none' => 'None',
        'open_ended' => 'Open-ended',
    ],
    'actions' => [
        'schedule' => 'Schedule maintenance',
        'clear' => 'Clear maintenance',
        'clear_confirmation' => 'Clear this maintenance window?',
    ],
    'messages' => [
        'scheduled' => 'Maintenance was scheduled for :count monitoring.|Maintenance was scheduled for :count monitorings.',
        'cleared' => 'Maintenance window was cleared.',
    ],
    'empty' => [
        'title' => 'No monitorings yet',
        'text' => 'Create a monitoring before planning maintenance.',
    ],
];
