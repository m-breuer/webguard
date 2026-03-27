<?php

declare(strict_types=1);

return [
    'terms_of_use' => [
        'seo' => [
            'title' => 'Terms of Use | WebGuard',
            'description' => 'Terms of Use for the non-commercial WebGuard demonstration platform.',
            'keywords' => 'webguard terms of use, legal terms, demo monitoring terms',
            'og_title' => 'WebGuard Terms of Use',
            'og_description' => 'Legal terms for using the WebGuard demonstration and testing platform.',
        ],
        'hero' => [
            'eyebrow' => 'Legal Terms',
            'title' => 'Terms of Use',
            'subtitle' => 'Rules for using the WebGuard demonstration platform.',
        ],
        'sections' => [
            'scope' => [
                'title' => 'Scope of Service',
                'intro' => 'WebGuard currently provides monitoring and operational support features, including:',
                'items' => [
                    'HTTP, ping, keyword, and port monitoring for configured targets.',
                    'SSL certificate checks and expiry warnings for supported endpoints.',
                    'Status and incident tracking, response-time insights, and uptime/downtime visualizations.',
                    'Public status labels when enabled by the user.',
                    'Channel-based notifications for incidents and SSL events (for example Slack, Telegram, Discord, custom webhooks).',
                    'Token-based API access for supported endpoints.',
                    'Optional GitHub sign-in and demo/guest access.',
                ],
            ],
            'obligations' => [
                'title' => 'User Obligations',
                'intro' => 'Users must comply with the following obligations:',
                'items' => [
                    'Provide accurate and complete account and monitoring information.',
                    'Monitor only systems, endpoints, and domains for which you are authorized.',
                    'Protect credentials, API tokens, and optional authentication data used in checks.',
                    'Use WebGuard only for lawful purposes.',
                    'Do not misuse, disrupt, overload, or interfere with the service (including excessive automated access).',
                    'Do not attempt unauthorized access to systems or data.',
                    'When enabling public labels, ensure no confidential information is exposed in monitoring names or target URLs.',
                ],
            ],
            'liability' => [
                'title' => 'Limitation of Liability',
                'body' => 'WebGuard is provided "as is" and "as available". We do not guarantee uninterrupted availability, specific response times, or complete alert coverage. Monitoring data may be delayed or inaccurate due to external factors (for example network conditions, third-party outages, or target-system configuration). To the extent legally permitted, we are not liable for indirect or consequential damages, lost profits, outages, or losses resulting from reliance on reported monitoring data. Mandatory statutory liability remains unaffected.',
            ],
            'termination' => [
                'title' => 'Termination Conditions',
                'body' => 'We may suspend or terminate accounts at any time, especially in cases of misuse, legal violations, security risks, or interference with platform operations. Users may stop using the service and delete their account at any time. As a non-commercial demonstration system, WebGuard may change, limit, or discontinue features at any time.',
            ],
            'governing_law' => [
                'title' => 'Governing Law and Dispute Resolution',
                'body' => 'These Terms are governed by the laws of the Federal Republic of Germany. If you are a consumer, mandatory consumer protection provisions of your country of residence remain unaffected. To the extent legally permitted, the place of jurisdiction is the operator\'s registered seat.',
            ],
            'contact' => [
                'title' => 'Contact Information',
                'body' => 'For legal inquiries or support requests, contact:',
            ],
            'non_commercial' => [
                'title' => 'Non-commercial Disclaimer',
                'body' => 'WebGuard is currently operated as a non-commercial project for demonstration and testing purposes only. In particular, there is no entitlement to paid service levels (SLA), compensation, or commercial support.',
            ],
        ],
        'footer_link' => 'Terms of Use',
    ],
    'privacy_policy' => [
        'seo' => [
            'title' => 'GDPR Privacy Notice | WebGuard',
            'description' => 'Privacy and data protection information for WebGuard.',
            'keywords' => 'webguard gdpr, privacy policy, data protection',
            'og_title' => 'WebGuard GDPR Privacy Notice',
            'og_description' => 'Information about personal data processing on WebGuard.',
        ],
        'hero' => [
            'eyebrow' => 'Data Protection',
            'title' => 'GDPR Privacy Notice',
            'subtitle' => 'How personal data is handled in the WebGuard demonstration environment.',
        ],
        'sections' => [
            'overview' => [
                'title' => 'Overview',
                'body' => 'WebGuard is currently operated as a non-commercial demonstration project. Personal data is processed only as required to provide account access, monitoring functions, and operational communication. For privacy-related requests, use the protected contact reveal action below.',
            ],
        ],
    ],
    'footer' => [
        'content' => 'All rights reserved.',
    ],
];
