<?php

declare(strict_types=1);

return [
    'title' => 'Gruppen',
    'create' => [
        'title' => 'Gruppe erstellen',
    ],
    'edit' => [
        'title' => ':group bearbeiten',
    ],
    'empty' => [
        'title' => 'Noch keine Gruppen',
        'text' => 'Erstellen Sie Gruppen, um Ihre Überwachungen zu organisieren und zu filtern.',
    ],
    'form' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
    ],
    'filter' => [
        'all' => 'Alle Gruppen',
        'label' => 'Gruppe',
    ],
    'monitorings_count' => ':count Überwachung|:count Überwachungen',
    'actions' => [
        'publish_status_page' => 'Als Statusseite veröffentlichen',
        'delete' => [
            'confirmation' => 'Möchten Sie diese Gruppe wirklich löschen? Die Überwachungen bleiben erhalten und verlieren nur diese Gruppenzuordnung.',
        ],
    ],
    'messages' => [
        'created' => 'Gruppe erfolgreich erstellt.',
        'updated' => 'Gruppe erfolgreich aktualisiert.',
        'deleted' => 'Gruppe erfolgreich gelöscht.',
        'status_page_created' => 'Statusseite für diese Gruppe erfolgreich erstellt.',
    ],
];
