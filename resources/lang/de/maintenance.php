<?php

declare(strict_types=1);

return [
    'title' => 'Wartung',
    'schedule' => [
        'heading' => 'Wartung planen',
        'description' => 'Planen Sie ein Wartungsfenster für eine einzelne Überwachung oder wenden Sie dasselbe Fenster auf alle Überwachungen einer Gruppe an.',
    ],
    'form' => [
        'mode' => 'Fenstertyp',
        'modes' => [
            'one_off' => 'Einmalig',
            'recurring' => 'Wiederkehrend',
        ],
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
        'recurring_starts_at' => 'Erster Termin',
        'recurrence' => 'Wiederholung',
        'recurrences' => [
            'weekly' => 'Wöchentlich',
            'monthly' => 'Monatlich',
        ],
        'duration' => 'Dauer (Minuten)',
        'repeat_until' => 'Wiederholen bis',
        'timezone' => 'Zeitzone',
        'recurring' => 'Wiederkehrendes Wartungsfenster',
        'help' => 'Während des Wartungsfensters werden Prüfungen übersprungen und der Status als UNBEKANNT gemeldet. Lassen Sie das Ende leer, wenn das Fenster offen bleiben soll.',
    ],
    'recurring' => [
        'heading' => 'Wiederkehrende Fenster',
        'description' => 'Wiederkehrende Wartungspläne bleiben aktiv, bis sie deaktiviert oder beendet werden.',
        'target' => 'Ziel',
        'schedule' => 'Zeitplan',
        'timezone' => 'Zeitzone',
        'starts' => 'Erster Termin',
    ],
    'windows' => [
        'heading' => 'Geplante Fenster',
        'description' => 'Aktuelle und kommende Fenster über alle Ihre Überwachungen hinweg.',
    ],
    'summary' => [
        'total' => 'Gesamt',
    ],
    'table' => [
        'groups' => 'Gruppen',
        'actions' => 'Aktionen',
        'status_filter' => 'Wartungsstatus',
        'group_filter' => 'Monitoring-Gruppe',
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
        'clear_recurring_confirmation' => 'Dieses wiederkehrende Wartungsfenster deaktivieren?',
    ],
    'messages' => [
        'scheduled' => 'Wartung wurde für :count Überwachung geplant.|Wartung wurde für :count Überwachungen geplant.',
        'cleared' => 'Wartungsfenster wurde entfernt.',
        'loading' => 'Wartungsdaten werden geladen …',
        'error' => 'Wartungsdaten konnten nicht geladen werden.',
        'recurring_scheduled' => 'Wiederkehrendes Wartungsfenster wurde geplant.',
        'recurring_cleared' => 'Wiederkehrendes Wartungsfenster wurde deaktiviert.',
    ],
    'empty' => [
        'title' => 'Noch keine Überwachungen',
        'text' => 'Erstellen Sie eine Überwachung, bevor Sie Wartung planen.',
    ],
];
