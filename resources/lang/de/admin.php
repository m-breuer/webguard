<?php

declare(strict_types=1);

return [
    'title' => 'Admin',
    'dashboard' => [
        'heading' => 'Admin-Dashboard',
        'users' => [
            'heading' => 'Benutzer verwalten',
            'description' => 'Benutzerkonten anzeigen, bearbeiten und löschen.',
        ],
        'packages' => [
            'heading' => 'Pakete verwalten',
            'description' => 'Pakete und Zugriffsbeschränkungen kontrollieren.',
        ],
        'apis' => [
            'heading' => 'API-Zugriff verwalten',
            'description' => 'API-Schlüssel generieren, Protokolle anzeigen und Nutzung verwalten.',
        ],
        'instances' => [
            'heading' => 'Serverinstanzen verwalten',
            'description' => 'Crawler-Instanz-Codes und interne API-Schlüssel verwalten.',
        ],
        'activity_logs' => [
            'heading' => 'Audit-Protokolle',
            'description' => 'Konto-, Profil-, API-Token- und Monitoring-Änderungen prüfen.',
        ],
        'infrastructure_health' => [
            'heading' => 'Infrastrukturzustand',
            'description' => 'Scheduler, Queue, Cache, Datenbank und Scanner prüfen.',
        ],
    ],
    'infrastructure_health' => [
        'title' => 'Infrastrukturzustand',
        'summary' => [
            'heading' => 'Status der Anwendungsinfrastruktur',
            'generated_at' => 'Erstellt am :timestamp',
        ],
        'statuses' => [
            'healthy' => 'Gesund',
            'warning' => 'Warnung',
            'critical' => 'Kritisch',
        ],
        'checks' => [
            'database' => [
                'title' => 'Datenbank',
                'healthy' => 'Die Datenbankverbindung :connection ist erreichbar.',
                'critical' => 'Die Datenbankverbindung :connection ist fehlgeschlagen.',
            ],
            'cache' => [
                'title' => 'Cache',
                'healthy' => 'Der Cache-Store :store hat eine Lese-/Schreibprobe akzeptiert.',
                'critical' => 'Der Cache-Store :store hat eine Lese-/Schreibprobe nicht bestanden.',
            ],
            'scheduler' => [
                'title' => 'Scheduler',
                'healthy' => 'Der Scheduler-Heartbeat wurde vor :minutes_since_last_seen Minuten geschrieben.',
                'missing' => 'Es wurde noch kein Scheduler-Heartbeat geschrieben.',
                'invalid' => 'Der Scheduler-Heartbeat-Wert ist ungueltig.',
                'stale' => 'Der Scheduler-Heartbeat ist mit :minutes_since_last_seen Minuten veraltet.',
                'critical' => 'Der Scheduler-Heartbeat konnte nicht aus dem Cache gelesen werden.',
            ],
            'queue' => [
                'title' => 'Queue',
                'healthy' => 'Die Queue :connection hat :failed_jobs fehlgeschlagene Jobs.',
                'failed_jobs' => 'Die Queue :connection hat :failed_jobs fehlgeschlagene Jobs.',
                'critical' => 'Der Speicher für fehlgeschlagene Jobs konnte nicht abgefragt werden.',
            ],
            'scanner_instances' => [
                'title' => 'Scannerinstanzen',
                'healthy' => 'Alle aktiven Scannerinstanzen melden sich aktuell.',
                'degraded' => 'Mindestens eine aktive Scannerinstanz ist veraltet oder wurde nie gesehen.',
                'none_active' => 'Es sind keine aktiven Scannerinstanzen konfiguriert.',
            ],
        ],
        'meta' => [
            'active_instances' => 'Aktive Instanzen',
            'cache_key' => 'Cache-Schluessel',
            'connection' => 'Verbindung',
            'empty' => 'n/a',
            'error' => 'Fehler',
            'failed_jobs' => 'Fehlgeschlagene Jobs',
            'healthy_instances' => 'Gesunde Instanzen',
            'inactive_instances' => 'Inaktive Instanzen',
            'last_seen_at' => 'Zuletzt gesehen',
            'minutes_since_last_seen' => 'Minuten seit zuletzt gesehen',
            'never_seen_instances' => 'Nie gesehene Instanzen',
            'stale_after_minutes' => 'Veraltet nach Minuten',
            'stale_instances' => 'Veraltete Instanzen',
            'store' => 'Store',
            'threshold' => 'Schwellwert',
            'total_instances' => 'Instanzen gesamt',
        ],
    ],
    'activity_logs' => [
        'title' => 'Audit-Protokolle',
        'filters' => [
            'log_name' => 'Protokoll',
            'event' => 'Ereignis',
            'actor' => 'Akteur',
            'subject_type' => 'Betroffener Typ',
            'subject_id' => 'Betroffene ID',
            'date_from' => 'Von',
            'date_to' => 'Bis',
            'apply' => 'Filter anwenden',
            'reset' => 'Zurücksetzen',
        ],
        'fields' => [
            'created_at' => 'Datum',
            'actor' => 'Akteur',
            'log_name' => 'Protokoll',
            'event' => 'Ereignis',
            'subject' => 'Betroffen',
            'description' => 'Beschreibung',
            'changes' => 'Änderungen',
        ],
        'subject_types' => [
            'user' => 'Benutzer',
            'monitoring' => 'Monitoring',
        ],
        'messages' => [
            'empty' => 'Keine Audit-Protokolle gefunden.',
            'anonymous' => 'System / anonym',
            'unknown_subject' => 'Unbekannter Datensatz',
            'show_changes' => 'Änderungen anzeigen',
            'hide_changes' => 'Änderungen ausblenden',
        ],
    ],
    'server_instances' => [
        'title' => 'Serverinstanzen',
        'fields' => [
            'code' => 'Instanzcode',
            'ip_address' => 'IPv4-Adresse',
            'api_key' => 'Instanz-API-Schlüssel',
            'status' => 'Status',
            'health' => 'Zustand',
            'last_seen_at' => 'Zuletzt gesehen',
            'monitorings' => 'Monitorings',
            'monitoring_types' => 'Typen',
            'never' => 'Nie',
            'none' => 'Keine',
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'actions' => 'Aktionen',
            'created_at' => 'Erstellt',
            'updated_at' => 'Aktualisiert',
        ],
        'summary' => [
            'total_instances' => 'Instanzen gesamt',
            'active_instances' => 'Aktive Instanzen',
            'stale_instances' => 'Veraltete Instanzen',
            'total_monitorings' => 'Zugewiesene Monitorings',
        ],
        'monitorings_count' => ':count Monitoring|:count Monitorings',
        'health' => [
            'healthy' => 'Gesund',
            'stale' => 'Veraltet',
            'never_seen' => 'Nie gesehen',
            'inactive' => 'Inaktiv',
        ],
        'messages' => [
            'confirm_delete' => 'Möchten Sie diese Instanz wirklich löschen?',
            'no_instances' => 'Keine Serverinstanzen gefunden.',
            'instance_created' => 'Serverinstanz erfolgreich erstellt.',
            'instance_updated' => 'Serverinstanz erfolgreich aktualisiert.',
            'instance_deleted' => 'Serverinstanz erfolgreich gelöscht.',
            'instance_in_use' => 'Serverinstanz wird verwendet und kann nicht gelöscht werden.',
            'api_key_optional' => 'Leer lassen, um den aktuellen API-Schlüssel zu behalten.',
        ],
        'create' => [
            'title' => 'Serverinstanz erstellen',
        ],
        'edit' => [
            'title' => 'Serverinstanz bearbeiten',
        ],
    ],
    'packages' => [
        'title' => 'Pakete',
        'fields' => [
            'monitoring_limit' => 'Überwachungslimit',
            'price' => 'Preis',
            'is_selectable' => 'Wählbar',
            'actions' => 'Aktionen',
            'yes' => 'Ja',
            'no' => 'Nein',
        ],
        'messages' => [
            'confirm_delete' => 'Sind Sie sicher, dass Sie dieses Paket löschen möchten?',
            'no_packages' => 'Keine Pakete gefunden.',
            'package_created' => 'Paket erfolgreich erstellt.',
            'package_updated' => 'Paket erfolgreich aktualisiert.',
            'package_in_use' => 'Paket wird verwendet und kann nicht gelöscht werden.',
            'package_deleted' => 'Paket erfolgreich gelöscht.',
        ],
        'create' => [
            'title' => 'Paket erstellen',
        ],
        'edit' => [
            'title' => 'Paket bearbeiten',
        ],
    ],
];
