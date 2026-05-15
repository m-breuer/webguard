<?php

declare(strict_types=1);

return [
    'title' => 'Statusseiten',
    'components_count' => ':count Komponente|:count Komponenten',
    'state' => [
        'public' => 'Öffentlich',
        'private' => 'Privat',
    ],
    'empty' => [
        'title' => 'Noch keine Statusseiten',
        'text' => 'Gruppieren Sie Überwachungen in kundenorientierte Komponenten wie API, Web App, Worker oder Datenbank.',
    ],
    'create' => [
        'title' => 'Statusseite erstellen',
    ],
    'edit' => [
        'title' => 'Statusseite bearbeiten: :statusPage',
    ],
    'form' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'slug_placeholder' => 'Wird aus dem Namen erzeugt, wenn leer',
        'description' => 'Beschreibung',
        'is_public' => 'Diese Statusseite veröffentlichen',
        'components' => 'Komponenten',
        'component_name' => 'Komponentenname',
        'component_description' => 'Komponentenbeschreibung',
        'monitorings' => 'Überwachungen',
        'add_component' => 'Komponente hinzufügen',
        'remove_component' => 'Komponente entfernen',
    ],
    'actions' => [
        'public_page' => 'Öffentliche Seite',
        'delete_confirmation' => 'Sind Sie sicher, dass Sie diese Statusseite löschen möchten?',
    ],
    'messages' => [
        'created' => 'Statusseite erfolgreich erstellt.',
        'updated' => 'Statusseite erfolgreich aktualisiert.',
        'deleted' => 'Statusseite erfolgreich gelöscht.',
    ],
    'incident_updates' => [
        'heading' => 'Vorfall-Updates',
        'description' => 'Veröffentlichen Sie manuelle Updates, damit Besucher den Vorfallsverlauf verfolgen können.',
        'status' => 'Status',
        'message' => 'Update-Nachricht',
        'add' => 'Update hinzufügen',
        'statuses' => [
            'investigating' => 'Wird untersucht',
            'identified' => 'Identifiziert',
            'monitoring' => 'Unter Beobachtung',
            'resolved' => 'Behoben',
        ],
        'messages' => [
            'created' => 'Vorfall-Update erfolgreich hinzugefügt.',
        ],
    ],
    'public' => [
        'title' => ':statusPage - Status',
        'overall_status' => 'Gesamtstatus',
        'recent_incidents' => 'Letzte Vorfälle',
    ],
    'validation' => [
        'fix_errors' => 'Bitte korrigieren Sie die markierten Statusseiten-Einstellungen.',
    ],
];
