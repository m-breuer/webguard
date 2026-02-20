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
    ],
    'server_instances' => [
        'title' => 'Serverinstanzen',
        'fields' => [
            'code' => 'Instanzcode',
            'api_key' => 'Instanz-API-Schlüssel',
            'status' => 'Status',
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'actions' => 'Aktionen',
            'created_at' => 'Erstellt',
            'updated_at' => 'Aktualisiert',
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
