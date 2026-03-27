<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Monitoring Locations and IP Ranges',
        'description' => 'Find all monitoring locations and source IP addresses used by WebGuard so you can allow-list traffic and avoid false alerts.',
        'keywords' => 'monitoring locations, monitoring IP addresses, allow-list monitoring, uptime monitoring IP ranges',
        'og_title' => 'Monitoring Locations and IP Ranges',
        'og_description' => 'Transparent list of monitoring locations and source IP addresses for allow-listing and stable uptime checks.',
    ],
    'hero' => [
        'eyebrow' => 'Monitoring Transparency',
        'title' => 'Monitoring Locations and Source IPs',
        'subtitle' => 'Use this list to allow-list monitoring traffic in firewalls, WAFs, and network policies so checks remain accurate.',
    ],
    'table' => [
        'caption' => 'Available monitoring locations',
        'location' => 'Location',
        'ip_range' => 'IP Address / Range',
        'ip_missing' => 'Not published yet',
        'empty' => 'No active monitoring locations are currently available.',
    ],
    'note' => [
        'title' => 'Why this matters',
        'text' => 'If source IPs are blocked by your infrastructure, checks may fail although your service is healthy. Allow-listing these IPs reduces false negatives.',
    ],
    'guidance' => [
        'title' => 'Important before enabling checks',
        'text' => 'Please verify that the IP addresses or ranges listed below are allow-listed in your infrastructure. If they are blocked, monitoring requests may be flagged as bot traffic and rejected.',
        'checklist_title' => 'Quick checklist',
        'items' => [
            '1' => 'Add the IP addresses to your firewall, WAF, or CDN allow-list.',
            '2' => 'Ensure security rules do not classify monitoring requests as bots.',
            '3' => 'Run a test check after changes and validate the result.',
        ],
    ],
    'footer_link' => 'Monitoring Locations',
];
