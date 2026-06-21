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
        'demo_monitorings' => [
            'heading' => 'Demo-Monitorings verwalten',
            'description' => 'Monitorings des Demo-Benutzers anzeigen, erstellen, bearbeiten und löschen.',
        ],
        'activity_logs' => [
            'heading' => 'Audit-Protokolle',
            'description' => 'Konto-, Profil-, API-Token- und Monitoring-Änderungen prüfen.',
        ],
    ],
    'demo_monitorings' => [
        'title' => 'Demo-Benutzer-Monitorings',
        'actions' => [
            'create' => 'Demo-Monitoring hinzufügen',
        ],
        'fields' => [
            'created_at' => 'Erstellt',
            'actions' => 'Aktionen',
        ],
        'summary' => [
            'demo_user' => 'Demo-Benutzer',
            'monitorings' => 'Monitorings',
            'package_limit' => 'Paketlimit',
        ],
        'messages' => [
            'empty' => 'Keine Monitorings für den Demo-Benutzer gefunden.',
            'confirm_delete' => 'Möchten Sie dieses Demo-Benutzer-Monitoring wirklich löschen?',
            'created' => 'Demo-Benutzer-Monitoring erfolgreich erstellt.',
            'updated' => 'Demo-Benutzer-Monitoring erfolgreich aktualisiert.',
            'deleted' => 'Demo-Benutzer-Monitoring erfolgreich gelöscht.',
        ],
        'create' => [
            'title' => 'Demo-Benutzer-Monitoring erstellen',
            'demo_user' => 'Monitoring-Besitzer',
        ],
        'edit' => [
            'title' => ':monitoring bearbeiten',
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
            'stale_instances' => 'Nicht erreichbare Instanzen',
            'total_monitorings' => 'Zugewiesene Monitorings',
        ],
        'monitorings_count' => ':count Monitoring|:count Monitorings',
        'health' => [
            'healthy' => 'Erreichbar',
            'stale' => 'Nicht erreichbar',
            'never_seen' => 'Noch kein Bericht',
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
