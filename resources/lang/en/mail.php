<?php

declare(strict_types=1);

return [
    'ssl_expiry_warning' => [
        'subject' => 'SSL Certificate Expiry Warning for :monitoringName',
        'greeting' => 'Hello!',
        'intro' => 'Your SSL certificate for **:monitoringName** (:monitoringTarget) is expiring soon!',
        'expiry_date' => 'It will expire on **:expiryDate**.',
        'action_text' => 'Please take action to renew your certificate to avoid any service interruptions.',
        'button_text' => 'View Monitoring Details',
        'salutation' => 'Thanks,',
    ],
    'status_change_notification' => [
        'subject' => 'Monitoring Status Change: :monitoringName',
        'greeting' => 'Hello :userName,',
        'intro' => 'This is to inform you that the status of your monitoring ":monitoringName" has changed.',
        'new_status' => 'New Status: :message',
        'button_text' => 'View Monitoring Page',
        'salutation' => 'Thank you,',
    ],
    'unread_notifications_reminder' => [
        'subject' => 'Action required on the platform',
        'greeting' => 'Hello :userName,',
        'intro' => 'You have :count unread notifications on the WebGuard platform.',
        'action_text' => 'Please visit your notifications page to review them.',
        'button_text' => 'View Notifications',
        'salutation' => 'Thank you,',
    ],
    'weekly_monitoring_digest' => [
        'subject' => 'Weekly monitoring digest (:from - :to)',
        'greeting' => 'Hello :userName,',
        'intro' => 'Here is your monitoring summary for :from through :to.',
        'overview_heading' => 'Overview',
        'monitorings_heading' => 'Monitorings',
        'warnings_heading' => 'Expiry warnings',
        'ssl_warnings_heading' => 'SSL certificates',
        'domain_warnings_heading' => 'Domains',
        'monitor_label' => 'Monitor',
        'uptime_label' => 'Uptime',
        'incidents_label' => 'Incidents',
        'longest_downtime_label' => 'Longest downtime',
        'no_data' => 'No data',
        'no_warnings' => 'No SSL or domain expiry warnings for this period.',
        'invalid_warning' => 'invalid or expired',
        'expires_on' => 'expires on :date',
        'minutes' => ':count min',
        'button_text' => 'View Monitorings',
        'salutation' => 'Thank you,',
    ],
    'general' => [
        'team_name' => 'The WebGuard Team',
        'legal' => 'Legal',
    ],
];
