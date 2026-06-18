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
        'public_label_enabled' => 'Öffentliches Gruppenlabel aktivieren',
        'public_label_help' => 'Das öffentliche Gruppenlabel zeigt nur Überwachungen dieser Gruppe, bei denen auch das eigene öffentliche Label aktiviert ist.',
    ],
    'filter' => [
        'all' => 'Alle Gruppen',
        'label' => 'Monitoring-Gruppe',
    ],
    'state' => [
        'private' => 'Privat',
        'public' => 'Öffentlich',
    ],
    'monitorings_count' => ':count Überwachung|:count Überwachungen',
    'actions' => [
        'public_label' => 'Öffentliches Label',
        'delete' => [
            'confirmation' => 'Möchten Sie diese Monitoring-Gruppe wirklich löschen? Die Überwachungen bleiben erhalten und verlieren nur diese Gruppenzuordnung.',
        ],
    ],
    'messages' => [
        'created' => 'Monitoring-Gruppe erfolgreich erstellt.',
        'updated' => 'Monitoring-Gruppe erfolgreich aktualisiert.',
        'deleted' => 'Monitoring-Gruppe erfolgreich gelöscht.',
    ],
    'public_label' => [
        'title' => ':groupName - Öffentlicher Status',
        'empty' => [
            'title' => 'Keine öffentlichen Überwachungen',
            'text' => 'Diese Gruppe enthält keine Überwachungen mit aktiviertem öffentlichem Label.',
        ],
    ],
];
