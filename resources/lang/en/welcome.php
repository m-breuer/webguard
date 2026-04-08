<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'WebGuard - Free Monitoring for Websites, APIs, Servers, and Ports',
        'description' => 'WebGuard is free-to-use monitoring software for HTTP, Ping, Keyword, and Port checks with notifications, SSL expiry tracking, uptime insights, and public status pages.',
        'keywords' => 'free monitoring software, uptime monitoring, website monitoring, ping monitoring, keyword monitoring, port monitoring, SSL expiry monitoring, status page, incident alerts',
        'og_title' => 'WebGuard - Monitor reliability with full transparency',
        'og_description' => 'Track availability and performance with HTTP, Ping, Keyword, and Port monitoring, clear notifications, and easy-to-read uptime reporting.',
    ],

    'nav' => [
        'aria' => 'Primary navigation',
        'logo_alt' => 'WebGuard logo',
        'features' => 'Features',
        'proof' => 'Context',
        'get_started' => 'Use for free',
        'login' => 'Login',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'eyebrow' => 'Free-to-use monitoring software',
        'title' => 'WebGuard delivers professional monitoring for teams and individual projects.',
        'subtitle' => 'The platform supports reliable monitoring for services and infrastructure and is available without license costs.',
        'primary_cta' => 'Start for Free',
        'secondary_cta' => 'Use Guest Access',
        'metrics' => [
            '1' => [
                'label' => 'License',
                'value' => 'Free to use for everyone',
            ],
            '2' => [
                'label' => 'Coverage',
                'value' => 'HTTP, Ping, Keyword, and Port',
            ],
            '3' => [
                'label' => 'Operation',
                'value' => '24/7 checks with flexible intervals',
            ],
        ],
    ],

    'feature_section' => [
        'eyebrow' => 'Complete Coverage',
        'title' => 'Everything you need to monitor service health',
        'subtitle' => 'WebGuard combines checks, notifications, and historical data in a clear workflow designed for everyday operations.',
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
            'text' => 'Receive incident updates through multiple channels so response stays fast and coordinated.',
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
            'badge' => 'Distribution',
            'title' => 'Monitoring from Multiple Locations',
            'text' => 'Cross-region checks help isolate local outages and reduce false positives.',
        ],
    ],

    'visuals' => [
        'eyebrow' => 'Product Overview',
        'title' => 'A clear workflow for operations and monitoring',
        'subtitle' => 'From dashboard summaries to monitor-level details and public labels, WebGuard keeps incident context in one place.',
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
                'text' => 'Share transparent availability updates in a clear, branded format.',
                'alt' => 'Public status preview displaying operational, degraded, and incident labels',
            ],
        ],
    ],

    'workflow' => [
        'eyebrow' => 'How It Works',
        'title' => 'From setup to incident resolution in three steps',
        'subtitle' => 'The onboarding path is intentionally simple while still offering the control needed for ongoing operations.',
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
        'eyebrow' => 'Principles',
        'title' => 'Transparent, free software for reliable monitoring',
        'subtitle' => 'WebGuard is provided as generally available software. The focus is stable functionality, clear visibility, and free access instead of product sales.',
    ],

    'testimonial' => [
        'quote' => 'Combining region selection, flexible intervals, and clear alerts makes it easier to detect incidents early and classify them accurately.',
    ],

    'case_study' => [
        'title' => 'Example setup for typical services',
        'text' => 'A robust baseline setup uses fixed intervals, multi-region checks, and clear alert rules per endpoint. This helps detect outages early and classify them as local or global faster.',
        'metrics' => [
            '1' => [
                'label' => 'Regions per check',
                'value' => 'DE',
            ],
            '2' => [
                'label' => 'Default interval',
                'value' => '1 minute|:count minutes',
            ],
            '3' => [
                'label' => 'Recommended monitor types',
                'value' => 'HTTP, Ping, Keyword, Port',
            ],
        ],
    ],

    'badges' => [
        'uptime' => [
            'title' => 'Stability Focus',
            'text' => 'The monitoring architecture is tuned for dependable availability checks and fast incident classification.',
        ],
        'transparent' => [
            'title' => 'Open and Understandable',
            'text' => 'Public status information and clear history make day-to-day system behavior transparent.',
        ],
    ],

    'final_cta' => [
        'title' => 'Use WebGuard for free',
        'text' => 'The software is available without a purchase model. Login or guest access is enough to explore the core features.',
        'primary' => 'Go to Login',
        'secondary' => 'Open Guest Access',
    ],

    'guest_login' => [
        'title' => 'Try WebGuard',
        'text' => 'Sign in with the guest account and explore dashboards, checks, and notifications in a live example environment. No registration required.',
        'button' => 'Login as Guest',
    ],
];
