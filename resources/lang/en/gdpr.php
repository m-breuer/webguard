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
                'Account and profile data (name, email, login metadata, locale, theme settings).',
                'Monitoring configuration data (target URLs, hostnames, ports, request methods, schedules, alert settings).',
                'Monitoring and usage logs (status changes, response times, notification status, audit-relevant actions).',
                'Notification channel settings and delivery metadata (email, Slack, Teams, WhatsApp, Discord, and similar channels when configured).',
                'Technical metadata (IP address, user agent, timestamps, session identifiers, API access data).',
                'SSL/TLS monitoring data (certificate validity dates, certificate metadata, related warning events).',
            ],
        ],
        'purposes_legal_basis' => [
            'title' => '3. Purposes and Legal Basis of Processing',
            'lead' => 'Data is processed only where needed for the operation and security of WebGuard.',
            'purposes_title' => 'Processing purposes',
            'purposes' => [
                'Provision and operation of the monitoring platform.',
                'Uptime and incident analysis, troubleshooting, and service quality improvement.',
                'Security, abuse prevention, fraud detection, and protection of system integrity.',
                'Delivery of monitoring alerts and service communications.',
            ],
            'legal_basis_title' => 'Legal basis under GDPR Art. 6',
            'legal_basis' => [
                'Art. 6(1)(b) GDPR (contract performance and pre-contractual measures).',
                'Art. 6(1)(c) GDPR (compliance with legal obligations, where applicable).',
                'Art. 6(1)(f) GDPR (legitimate interests in reliable, secure platform operation).',
                'Art. 6(1)(a) GDPR (consent, where consent is explicitly requested).',
            ],
        ],
        'third_party' => [
            'title' => '4. Use of Third-Party Services and Processors',
            'lead' => 'WebGuard may use external providers to deliver core features and integrations.',
            'items' => [
                'Hosting and infrastructure providers (compute, storage, network, backups).',
                'Monitoring and availability check infrastructure (including SSL and endpoint checks).',
                'Notification providers and integrations (for example Teams, Slack, WhatsApp, Discord, email delivery services).',
                'Operational tooling required for secure and reliable service delivery.',
            ],
            'note' => 'Where required, processing agreements under Art. 28 GDPR are in place.',
        ],
        'cookies' => [
            'title' => '5. Cookies and User Options',
            'lead' => 'WebGuard uses technically necessary cookies to provide login and session functionality.',
            'items' => [
                'Session cookies for authentication and secure account usage.',
                'Preference cookies (for example language settings) to improve usability.',
                'Optional analytics or tracking cookies are only used if explicitly enabled and lawfully permitted.',
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
            'lead' => 'Personal data is stored only as long as needed for contractual, legal, and operational purposes. Data is deleted or anonymized once retention obligations no longer apply.',
        ],
        'security' => [
            'title' => '8. Security Measures',
            'lead' => 'WebGuard applies appropriate technical and organizational measures to protect personal data against unauthorized access, loss, or manipulation.',
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
