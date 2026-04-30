<?php

declare(strict_types=1);

return [
    'title' => 'Notifications',
    'status_change_notifications' => 'Status Change',
    'status_board' => [
        'heading' => 'Notification Board',
    ],
    'ssl_expiry_notifications' => 'SSL Expiry',
    'domain_expiry_notifications' => 'Domain Expiry',
    'delivery_history' => [
        'heading' => 'Delivery History',
    ],
    'load_more' => 'Load More',
    'mark_as_read' => 'Mark as Read',
    'mark_all_as_read' => 'Mark all as read',
    'read' => 'Read',
    'no_notifications' => 'Nothing to discover. Everything is up to date.',
    'no_notifications_of_this_type' => 'No notifications of this type.',
    'show_read_notifications' => 'Show Read Notifications',
    'labels' => [
        'monitor' => 'Type',
        'host' => 'Host',
        'timestamp' => 'Latest check',
        'latest_status_change' => 'Latest status change',
        'channel' => 'Channel',
        'event' => 'Event',
        'attempted_at' => 'Attempted at',
        'sent_at' => 'Sent at',
        'error' => 'Error',
        'no_status_code' => 'No status code',
        'not_available' => 'Not available',
    ],
    'tooltips' => [
        'latest_status' => 'Latest status: :status',
    ],
    'status' => [
        'success' => 'Successful',
        'redirect' => 'Redirect',
        'client_error' => 'Client Error',
        'server_error' => 'Server Error',
        'unknown' => 'Unknown',
        'maintenance' => 'Maintenance',
    ],
    'status_change' => [
        'up' => 'Latest status change: monitor recovered.',
        'down' => 'Latest status change: monitor is down.',
        'unknown' => 'Latest status change: status is unknown.',
        'maintenance' => 'Latest status change: monitoring is in maintenance mode.',
    ],
    'status_messages' => [
        'up' => 'Monitoring :name status changed to UP',
        'down' => 'Monitoring :name status changed to DOWN',
    ],
    'ssl_messages' => [
        'expiring' => 'SSL certificate for :name is expiring soon.',
        'expired' => 'SSL certificate for :name has expired.',
    ],
    'domain_messages' => [
        'expiring' => 'Domain :name is expiring soon.',
        'expired' => 'Domain :name has expired.',
    ],
    'channels' => [
        'slack' => 'Slack',
        'telegram' => 'Telegram',
        'discord' => 'Discord',
        'webhook' => 'Webhook',
    ],
    'events' => [
        'incident' => 'Incident',
        'recovery' => 'Recovery',
        'ssl_expiring' => 'SSL expiring',
        'ssl_expired' => 'SSL expired',
        'domain_expiring' => 'Domain expiring',
        'domain_expired' => 'Domain expired',
    ],
    'delivery_status' => [
        'sent' => 'Sent',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
    ],
    'messages' => [
        'notification_marked_as_read' => 'Notification marked as read.',
        'all_notifications_marked_as_read' => 'All notifications marked as read.',
    ],
];
