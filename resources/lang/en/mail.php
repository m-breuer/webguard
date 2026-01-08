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
    'general' => [
        'team_name' => 'The WebGuard Team',
    ],
];
