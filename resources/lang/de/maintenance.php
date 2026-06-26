<?php

declare(strict_types=1);

return [
    'title' => 'Wartung',
    'schedule' => [
        'heading' => 'Wartung planen',
        'description' => 'Planen Sie ein Wartungsfenster für eine einzelne Überwachung oder wenden Sie dasselbe Fenster auf alle Überwachungen einer Gruppe an.',
    ],
    'form' => [
        'scope' => 'Anwenden auf',
        'scopes' => [
            'monitoring' => 'Einzelne Überwachung',
            'group' => 'Überwachungsgruppe',
        ],
        'monitoring' => 'Überwachung',
        'group' => 'Gruppe',
        'select_monitoring' => 'Überwachung auswählen',
        'select_group' => 'Gruppe auswählen',
        'from' => 'Wartung von',
        'until' => 'Wartung bis',
        'help' => 'Während des Wartungsfensters werden Prüfungen übersprungen und der Status als UNBEKANNT gemeldet. Lassen Sie das Ende leer, wenn das Fenster offen bleiben soll.',
    ],
    'windows' => [
        'heading' => 'Geplante Fenster',
        'description' => 'Aktuelle und kommende Fenster über alle Ihre Überwachungen hinweg.',
    ],
    'status' => [
        'active' => 'Aktiv',
        'upcoming' => 'Geplant',
        'expired' => 'Abgelaufen',
        'none' => 'Keine',
        'open_ended' => 'Ohne Endzeit',
    ],
    'actions' => [
        'schedule' => 'Wartung planen',
        'clear' => 'Wartung entfernen',
        'clear_confirmation' => 'Dieses Wartungsfenster entfernen?',
    ],
    'messages' => [
        'scheduled' => 'Wartung wurde für :count Überwachung geplant.|Wartung wurde für :count Überwachungen geplant.',
        'cleared' => 'Wartungsfenster wurde entfernt.',
    ],
    'empty' => [
        'title' => 'Noch keine Überwachungen',
        'text' => 'Erstellen Sie eine Überwachung, bevor Sie Wartung planen.',
    ],
];
