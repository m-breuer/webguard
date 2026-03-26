<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Privacy Policy | WebGuard',
        'description' => 'Privacy Policy according to GDPR for WebGuard.',
        'keywords' => 'privacy policy, gdpr, data protection, webguard',
        'og_title' => 'WebGuard Privacy Policy',
        'og_description' => 'How WebGuard processes personal data under GDPR.',
    ],
    'footer_link' => 'Privacy Policy',
    'hero' => [
        'eyebrow' => 'Data Protection',
        'title' => 'Privacy Policy',
        'subtitle' => 'Information on the processing of personal data in accordance with the GDPR.',
        'last_updated' => 'Last updated: :date',
        'last_updated_date' => 'March 24, 2026',
    ],
    'sections' => [
        'controller' => [
            'title' => '1. Responsible Entity (Data Controller)',
            'lead' => 'The operator listed below is responsible for data processing on this platform.',
        ],
        'data_categories' => [
            'title' => '2. Categories of Personal Data Processed',
            'lead' => 'Depending on your usage, WebGuard processes the following data categories:',
            'items' => [
                'Account and profile data (name, email, password hash, role, locale, theme settings, and optional avatar).',
                'Authentication and session data (login timestamps, session identifiers, IP address, user agent, and required session/CSRF metadata).',
                'Consent and auditability data (timestamps of acceptance for Terms of Use and Privacy Policy).',
                'Monitoring configuration data (name, monitoring type, target, port, keyword, HTTP method, headers/body, optional credentials, preferred location, maintenance window, public label setting).',
                'Monitoring result data (status, HTTP status codes, response times, SSL/TLS certificate data, incidents, and daily uptime/downtime aggregates).',
                'Notification data (channel configuration, delivery status, and read state).',
                'API data (personal access tokens, logged API routes, timestamps).',
                'Optional for GitHub login: GitHub ID, OAuth token/refresh token, avatar URL, and linked email address.',
            ],
        ],
        'purposes_legal_basis' => [
            'title' => '3. Purposes and Legal Basis of Processing',
            'lead' => 'Data is processed only where needed for the operation and security of WebGuard.',
            'purposes_title' => 'Processing purposes',
            'purposes' => [
                'Providing registration, login (including optional GitHub login), account management, and authentication.',
                'Performing monitoring checks (HTTP, ping, keyword, port), incident detection, and uptime/performance reporting.',
                'Sending service-related messages (for example verification/password reset emails and incident/SSL alerts via configured channels).',
                'Providing and securing API access (token handling, abuse protection, usage logging).',
                'Security and operations (troubleshooting, fault analysis, integrity protection).',
            ],
            'legal_basis_title' => 'Legal basis under GDPR Art. 6',
            'legal_basis' => [
                'Art. 6(1)(b) GDPR (contract performance and pre-contractual measures).',
                'Art. 6(1)(f) GDPR (legitimate interests in reliable, secure platform operation).',
                'Art. 6(1)(c) GDPR (compliance with legal obligations, where applicable).',
                'Art. 6(1)(a) GDPR (consent, where consent is explicitly requested).',
            ],
        ],
        'third_party' => [
            'title' => '4. Use of Third-Party Services and Processors',
            'lead' => 'WebGuard uses external providers only where necessary to operate the service.',
            'items' => [
                'Hosting and infrastructure providers (compute, storage, network, backups).',
                'Email delivery providers for transactional account emails (for example verification and password reset).',
                'Third-party APIs/webhook endpoints for user-configured notification channels (for example Slack, Telegram, Discord, custom webhooks).',
                'GitHub as OAuth provider when you choose GitHub sign-in.',
                'Operational and security tooling required for stable and secure delivery.',
            ],
            'note' => 'Where required, processing agreements under Art. 28 GDPR are in place. If providers process data outside the EU/EEA, this is done only under the safeguards required by Art. 44 et seq. GDPR.',
        ],
        'cookies' => [
            'title' => '5. Cookies and User Options',
            'lead' => 'WebGuard uses technically necessary cookies to provide login and session functionality.',
            'items' => [
                'Session cookies for authentication and secure account usage.',
                'Security/CSRF cookies required for protected forms and sessions.',
                'Preference cookie for language selection (`webguard_locale`).',
                'No marketing or analytics tracking cookies are currently used.',
            ],
            'options' => 'You can configure your browser to block or delete cookies. Blocking required cookies may limit platform functionality.',
        ],
        'rights' => [
            'title' => '6. Your Rights under GDPR',
            'lead' => 'You have the following rights subject to legal requirements:',
            'items' => [
                'Right of access (Art. 15 GDPR).',
                'Right to rectification (Art. 16 GDPR).',
                'Right to erasure (Art. 17 GDPR).',
                'Right to restriction of processing (Art. 18 GDPR).',
                'Right to data portability (Art. 20 GDPR).',
                'Right to object (Art. 21 GDPR).',
                'Right to withdraw consent at any time with future effect (Art. 7(3) GDPR).',
                'Right to lodge a complaint with a supervisory authority (Art. 77 GDPR).',
            ],
        ],
        'retention' => [
            'title' => '7. Storage Duration',
            'lead' => 'Personal data is stored only as long as needed for contractual, legal, and operational purposes. In the current app configuration, read notifications are regularly deleted after about one month, and guest notifications are removed after about one week. Older raw monitoring responses are regularly moved to an archive table. When accounts or monitorings are deleted, related data is removed as part of technical deletion workflows.',
        ],
        'security' => [
            'title' => '8. Security Measures',
            'lead' => 'WebGuard applies appropriate technical and organizational measures to protect personal data against unauthorized access, loss, or manipulation. These include role-based access control, token-based API authentication, and hashed storage of passwords and instance API keys.',
        ],
        'contact' => [
            'title' => '9. Data Protection Contact',
            'lead' => 'For privacy and data protection inquiries, please contact the operator using the details below.',
            'complaint' => 'If you believe your data protection rights are violated, you may contact a competent supervisory authority.',
        ],
    ],
    'fields' => [
        'operator_name' => 'Operator',
        'address' => 'Address',
        'email' => 'Email',
        'phone' => 'Phone',
    ],
];
