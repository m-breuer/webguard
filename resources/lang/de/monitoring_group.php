<?php

declare(strict_types=1);

return [
    'title' => 'Monitoring-Gruppen',
    'create' => [
        'title' => 'Monitoring-Gruppe erstellen',
    ],
    'edit' => [
        'title' => ':group bearbeiten',
    ],
    'empty' => [
        'title' => 'Noch keine Monitoring-Gruppen',
        'text' => 'Erstellen Sie Gruppen, um Ihre Überwachungen zu organisieren und zu filtern.',
    ],
    'form' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
    ],
    'filter' => [
        'all' => 'Alle Gruppen',
        'label' => 'Monitoring-Gruppe',
    ],
    'monitorings_count' => ':count Überwachung|:count Überwachungen',
    'actions' => [
        'publish_status_page' => 'Als Statusseite veröffentlichen',
        'delete' => [
            'confirmation' => 'Möchten Sie diese Monitoring-Gruppe wirklich löschen? Die Überwachungen bleiben erhalten und verlieren nur diese Gruppenzuordnung.',
        ],
    ],
    'messages' => [
        'created' => 'Monitoring-Gruppe erfolgreich erstellt.',
        'updated' => 'Monitoring-Gruppe erfolgreich aktualisiert.',
        'deleted' => 'Monitoring-Gruppe erfolgreich gelöscht.',
        'status_page_created' => 'Statusseite für diese Monitoring-Gruppe erfolgreich erstellt.',
    ],
];
