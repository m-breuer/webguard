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
    ],
    'server_instances' => [
        'title' => 'Serverinstanzen',
        'list' => 'Liste der Serverinstanzen',
        'link_to_instance' => 'Zur Instanz',
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
