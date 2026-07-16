<?php

declare(strict_types=1);

return [
    'analytics' => [
        'title' => 'Incident analytics',
        'description' => 'Analyze incident frequency, impact, recurrence, and recovery time for your monitorings.',
        'link' => 'Incident analytics',
        'filters' => [
            'period' => 'Period',
            'type' => 'Type',
            'severity' => 'Severity',
            'customer_impact' => 'Customer impact',
            'affected_service' => 'Affected service',
            'all' => 'All',
            'days_30' => 'Last 30 days',
            'days_90' => 'Last 90 days',
            'days_365' => 'Last 365 days',
            'apply' => 'Apply filters',
        ],
        'metrics' => [
            'total' => 'Incidents',
            'resolved' => 'Resolved',
            'open' => 'Open',
            'mttr' => 'Average MTTR',
            'minutes' => ':value min',
            'not_available' => 'n/a',
        ],
        'sections' => [
            'by_type' => 'By type',
            'by_severity' => 'By severity',
            'by_impact' => 'By customer impact',
            'by_service' => 'By affected service',
            'recurrence' => 'Recurring services',
            'recent' => 'Incidents in selected period',
        ],
        'definitions' => 'Incident count includes incidents opened in the selected period. MTTR is the average time between down and recovery for resolved incidents. Recurrence lists services with more than one incident.',
        'unclassified' => 'Unclassified',
        'empty' => 'No incidents match the selected filters.',
    ],
    'types' => [
        'availability' => 'Availability',
        'performance' => 'Performance',
        'security' => 'Security',
        'dependency' => 'Dependency',
        'configuration' => 'Configuration',
        'other' => 'Other',
        'unclassified' => 'Unclassified',
    ],
    'severities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
        'unclassified' => 'Unclassified',
    ],
    'customer_impacts' => [
        'none' => 'None',
        'degraded' => 'Degraded',
        'outage' => 'Outage',
        'unknown' => 'Unknown',
        'unclassified' => 'Unclassified',
    ],
    'contributing_categories' => [
        'code' => 'Code',
        'infrastructure' => 'Infrastructure',
        'dependency' => 'Dependency',
        'configuration' => 'Configuration',
        'process' => 'Process',
        'unknown' => 'Unknown',
    ],
];
