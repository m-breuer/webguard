<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'WebGuard - Uptime Monitoring, Alerts, and Public Status Pages',
        'description' => 'WebGuard helps teams monitor HTTP, Ping, Keyword, and Port checks with instant alerts, SSL expiry tracking, uptime insights, and transparent public status pages.',
        'keywords' => 'uptime monitoring, website monitoring, ping monitoring, keyword monitoring, port monitoring, SSL expiry monitoring, status page, incident alerts',
        'og_title' => 'WebGuard - Monitor Uptime, Performance, and Incidents Early',
        'og_description' => 'Detect outages early with HTTP, Ping, Keyword, and Port monitoring, multi-channel notifications, SSL checks, and clear uptime reporting.',
    ],

    'nav' => [
        'aria' => 'Primary navigation',
        'logo_alt' => 'WebGuard logo',
        'features' => 'Features',
        'proof' => 'Proof',
        'get_started' => 'Try the project',
        'login' => 'Login',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'eyebrow' => 'Personal monitoring project',
        'title' => 'WebGuard is my personal service for reliable monitoring.',
        'subtitle' => 'I built WebGuard to monitor my own websites and APIs. This instance is publicly available so you can test the features without any commercial commitment.',
        'primary_cta' => 'Start Monitoring in Minutes',
        'secondary_cta' => 'Open demo access',
        'metrics' => [
            '1' => [
                'label' => 'Setup',
                'value' => 'Personal project for daily monitoring',
            ],
            '2' => [
                'label' => 'Intervals',
                'value' => '24/7 checks with flexible cadence',
            ],
            '3' => [
                'label' => 'Regions',
                'value' => 'Selectable per monitor for faster diagnosis',
            ],
        ],
    ],

    'feature_section' => [
        'eyebrow' => 'Complete Coverage',
        'title' => 'Everything you need to monitor service health',
        'subtitle' => 'These are the capabilities I rely on in daily use: practical checks, useful alerts, and reliable history in one place.',
    ],

    'features' => [
        'http' => [
            'badge' => 'Core',
            'title' => 'HTTP Monitoring',
            'text' => 'Monitor API and website endpoints with latency and status-code validation.',
        ],
        'ping' => [
            'badge' => 'Core',
            'title' => 'Ping Monitoring',
            'text' => 'Track host reachability and network path stability with lightweight checks.',
        ],
        'keyword' => [
            'badge' => 'Core',
            'title' => 'Keyword Monitoring',
            'text' => 'Validate critical text content and detect broken pages or rendering regressions.',
        ],
        'port' => [
            'badge' => 'Core',
            'title' => 'Port Monitoring',
            'text' => 'Verify key service ports stay open and reachable for your infrastructure.',
        ],
        'notifications' => [
            'badge' => 'Alerts',
            'title' => 'Incident and Status Notifications',
            'text' => 'Receive incident updates through multiple channels, so teams react quickly and consistently.',
        ],
        'ssl' => [
            'badge' => 'Security',
            'title' => 'SSL Certificate Expiry Checks',
            'text' => 'Avoid certificate surprises with clear expiry tracking and proactive warning windows.',
        ],
        'stats' => [
            'badge' => 'Insights',
            'title' => 'Response Time and Uptime Analytics',
            'text' => 'Analyze trends, compare monitor behavior, and report on long-term reliability.',
        ],
        'multi_location' => [
            'badge' => 'Rolling Out',
            'title' => 'Monitoring from Multiple Locations',
            'text' => 'Cross-region checks help isolate local outages and reduce false positives.',
        ],
    ],

    'visuals' => [
        'eyebrow' => 'Product Preview',
        'title' => 'A focused workflow for operators and teams',
        'subtitle' => 'From high-level dashboards to monitor-level diagnostics and public labels, WebGuard keeps the full incident context in one place.',
        'previews' => [
            'dashboard' => [
                'title' => 'Dashboard Overview',
                'text' => 'Track fleet health at a glance with uptime summaries and response trends.',
                'alt' => 'WebGuard dashboard preview with uptime trends and service cards',
            ],
            'detail' => [
                'title' => 'Monitoring Detail View',
                'text' => 'Investigate spikes and incidents with per-monitor history and signal breakdowns.',
                'alt' => 'Monitoring detail preview showing incident timeline and response metrics',
            ],
            'public_status' => [
                'title' => 'Public Status Labels',
                'text' => 'Share transparent availability updates with customers in a clear, branded format.',
                'alt' => 'Public status preview displaying operational, degraded, and incident labels',
            ],
        ],
    ],

    'workflow' => [
        'eyebrow' => 'How It Works',
        'title' => 'From setup to incident resolution in three steps',
        'subtitle' => 'WebGuard is optimized for fast onboarding while still giving experienced teams the control they expect.',
        'steps' => [
            '1' => [
                'title' => 'Create monitors',
                'text' => 'Add HTTP, Ping, Keyword, or Port checks and set your target interval.',
            ],
            '2' => [
                'title' => 'Define alerts',
                'text' => 'Choose notification channels and receive incident updates without delay.',
            ],
            '3' => [
                'title' => 'Share status',
                'text' => 'Use status labels and uptime history to communicate reliability with stakeholders.',
            ],
        ],
    ],

    'trust' => [
        'eyebrow' => 'Project context',
        'title' => 'Why I built WebGuard',
        'subtitle' => 'WebGuard started as a personal monitoring project. This public instance is for testing and exploration, not as a commercial product sale.',
    ],

    'testimonial' => [
        'quote' => 'Region selection and flexible intervals are the strongest parts for me. I can quickly tell whether an issue is regional, and my monitors have become much more reliable.',
    ],

    'case_study' => [
        'title' => 'Real-world setup from my own stack',
        'text' => 'For my services, I run fixed intervals, multi-region checks, and clear alert rules per endpoint. That helps me detect outages early and classify them as local or global faster.',
        'metrics' => [
            '1' => [
                'label' => 'Regions per check',
                'value' => 'EU + US',
            ],
            '2' => [
                'label' => 'Default interval',
                'value' => '60 seconds',
            ],
            '3' => [
                'label' => 'Active monitor types',
                'value' => 'HTTP, Ping, Keyword, Port',
            ],
        ],
    ],

    'badges' => [
        'uptime' => [
            'title' => 'Uptime Focus',
            'text' => 'The monitoring setup is tuned for stable availability checks and fast outage classification.',
        ],
        'transparent' => [
            'title' => 'Transparent Monitoring',
            'text' => 'Public status information and clear history make day-to-day service behavior easy to understand.',
        ],
    ],

    'final_cta' => [
        'title' => 'Try WebGuard without commitment',
        'text' => 'There is no product sale here. You can test the service directly via login or with the demo access.',
        'primary' => 'Go to Login',
        'secondary' => 'Start Demo Access',
    ],

    'guest_login' => [
        'title' => 'Explore WebGuard',
        'text' => 'Curious to see how WebGuard works? Log in with our guest account and start monitoring popular sites like Google or Twitter. No registration required.',
        'button' => 'Login as Guest',
    ],
];
