<?php

declare(strict_types=1);

return [
    'title' => 'Benachrichtigungen',
    'status_change_notifications' => 'Statusänderung',
    'status_board' => [
        'heading' => 'Status-Board',
    ],
    'ssl_expiry_notifications' => 'SSL-Ablauf',
    'domain_expiry_notifications' => 'Domain-Ablauf',
    'delivery_history' => [
        'heading' => 'Zustellverlauf',
    ],
    'load_more' => 'Mehr laden',
    'mark_as_read' => 'Als gelesen markieren',
    'mark_all_as_read' => 'Alle als gelesen markieren',
    'read' => 'Gelesen',
    'no_notifications' => 'Nichts zu entdecken. Alles ist auf dem neuesten Stand.',
    'no_notifications_of_this_type' => 'Keine Benachrichtigungen dieses Typs.',
    'show_read_notifications' => 'Gelesene Benachrichtigungen anzeigen',
    'labels' => [
        'monitor' => 'Typ',
        'host' => 'Host',
        'timestamp' => 'Letzte Prüfung',
        'latest_status_change' => 'Letzte Statusänderung',
        'channel' => 'Kanal',
        'event' => 'Ereignis',
        'attempted_at' => 'Versucht am',
        'sent_at' => 'Gesendet am',
        'error' => 'Fehler',
        'no_status_code' => 'Kein Statuscode',
        'not_available' => 'Nicht verfügbar',
    ],
    'tooltips' => [
        'latest_status' => 'Letzter Status: :status',
    ],
    'status' => [
        'success' => 'Erfolgreich',
        'redirect' => 'Weiterleitung',
        'client_error' => 'Client-Fehler',
        'server_error' => 'Server-Fehler',
        'unknown' => 'Unbekannt',
        'maintenance' => 'Wartung',
    ],
    'status_change' => [
        'up' => 'Letzte Statusänderung: Überwachung wiederhergestellt.',
        'down' => 'Letzte Statusänderung: Überwachung ist nicht erreichbar.',
        'unknown' => 'Letzte Statusänderung: Status ist unbekannt.',
        'maintenance' => 'Letzte Statusänderung: Überwachung befindet sich im Wartungsmodus.',
    ],
    'status_messages' => [
        'up' => 'Status der Überwachung :name wurde auf VERFÜGBAR geändert',
        'down' => 'Status der Überwachung :name wurde auf NICHT VERFÜGBAR geändert',
    ],
    'ssl_messages' => [
        'expiring' => 'Das SSL-Zertifikat für :name läuft bald ab.',
        'expired' => 'Das SSL-Zertifikat für :name ist abgelaufen.',
    ],
    'domain_messages' => [
        'expiring' => 'Die Domain :name läuft bald ab.',
        'expired' => 'Die Domain :name ist abgelaufen.',
    ],
    'channels' => [
        'slack' => 'Slack',
        'telegram' => 'Telegram',
        'discord' => 'Discord',
        'webhook' => 'Webhook',
    ],
    'events' => [
        'incident' => 'Vorfall',
        'recovery' => 'Wiederherstellung',
        'ssl_expiring' => 'SSL läuft bald ab',
        'ssl_expired' => 'SSL abgelaufen',
        'domain_expiring' => 'Domain läuft bald ab',
        'domain_expired' => 'Domain abgelaufen',
    ],
    'delivery_status' => [
        'sent' => 'Gesendet',
        'failed' => 'Fehlgeschlagen',
        'skipped' => 'Übersprungen',
    ],
    'messages' => [
        'notification_marked_as_read' => 'Benachrichtigung als gelesen markiert.',
        'all_notifications_marked_as_read' => 'Alle Benachrichtigungen als gelesen markiert.',
    ],
];
