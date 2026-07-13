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
        'monitorings' => 'Überwachungen',
        'no_monitorings' => 'Keine Überwachungen ausgewählt',
        'search_monitorings' => 'Überwachungen nach Name oder Ziel suchen ...',
        'select_all_monitorings' => 'Alle auswählen',
        'all_monitorings_selected' => 'Alle passenden Überwachungen sind ausgewählt.',
        'no_monitorings_available' => 'Es sind keine privaten Überwachungen vorhanden.',
        'no_monitorings_found' => 'Keine passenden Überwachungen gefunden.',
        'remove_monitoring' => 'Überwachung entfernen:',
        'clear_monitorings' => 'Alle Überwachungen entfernen',
        'monitorings_help' => 'Optional: Ordnen Sie der Gruppe beim Speichern Überwachungen zu.',
    ],
    'validation' => [
        'monitoring_not_manageable' => 'Die ausgewählte Überwachung kann Ihrer Gruppe nicht zugeordnet werden.',
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
