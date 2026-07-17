<?php

declare(strict_types=1);

return [
    'title' => 'Betriebsübersicht',
    'greeting' => 'Guten Morgen, :name',
    'description' => 'Sieh sofort, was Aufmerksamkeit braucht, und öffne direkt die nächste sinnvolle Aktion.',
    'view_all' => 'Alle anzeigen',
    'open_monitorings' => 'Alle Monitorings anzeigen',
    'state' => [
        'new' => [
            'title' => 'Überwache deine Services',
            'description' => 'Erstelle dein erstes Monitoring, um diese Seite als tägliche Betriebsübersicht zu nutzen.',
        ],
        'healthy' => [
            'title' => 'Alles ist gesund',
            'description' => 'Deine aktiven Monitorings melden normale Zustände.',
        ],
        'degraded' => [
            'title' => 'Aktion erforderlich',
            'description' => ':count Monitoring meldet aktuell ein Problem.|:count Monitorings melden aktuell ein Problem.',
        ],
        'attention' => [
            'title' => 'Einige Checks brauchen Aufmerksamkeit',
            'description' => 'Bei :count Monitoring fehlt ein aktuelles verwertbares Ergebnis.|Bei :count Monitorings fehlen aktuelle verwertbare Ergebnisse.',
        ],
    ],
    'summary' => [
        'total' => 'Alle Monitorings',
        'healthy' => 'Gesund',
        'down' => 'Ausgefallen',
        'unknown' => 'Unbekannt oder veraltet',
        'paused' => 'Pausiert',
        'maintenance' => 'Wartung aktiv',
    ],
    'attention' => [
        'heading' => 'Braucht Aufmerksamkeit',
        'description' => 'Priorisierte Hinweise aus den Monitorings, auf die du Zugriff hast.',
        'incident' => ':name hat einen offenen Incident.',
        'down' => ':name meldet aktuell einen Ausfall.',
        'unknown' => ':name hat noch kein verwertbares Ergebnis gemeldet.',
        'stale' => ':name hat kürzlich kein Ergebnis gemeldet.',
        'delivery' => ':count fehlgeschlagene Benachrichtigungszustellungen in den letzten 7 Tagen.',
        'open' => 'Details öffnen',
        'open_incident' => 'Incident-Arbeitsbereich öffnen',
        'status_page' => 'Response-Arbeitsbereich: :name',
        'empty' => 'Keine aktiven Aufmerksamkeitspunkte. Behalte die Übersicht oben im Blick.',
    ],
    'quick_actions' => [
        'heading' => 'Schnellaktionen',
        'create' => 'Monitoring erstellen',
        'maintenance' => 'Wartung planen',
        'incidents' => 'Incidents prüfen',
        'notifications' => 'Benachrichtigungen öffnen',
        'status_pages' => 'Statusseiten verwalten',
    ],
    'next_action' => [
        'heading' => 'Nächster sinnvoller Schritt',
        'description' => 'Eine klare nächste Aktion, damit dein Betrieb reibungslos weiterläuft.',
    ],
    'maintenance' => [
        'heading' => 'Wartungsfenster',
        'description' => 'Aktive und bevorstehende Fenster deiner Monitorings.',
        'active' => 'Aktiv',
        'upcoming' => 'Bevorstehend',
        'open' => 'Wartung öffnen',
        'empty' => 'Keine aktiven oder bevorstehenden Wartungsfenster.',
    ],
    'incidents' => [
        'heading' => 'Letzte Incidents',
        'description' => 'Die neuesten Incidents aus deinen zugänglichen Monitorings.',
        'ongoing' => 'Laufend',
        'resolved' => 'Behoben',
        'open' => 'Monitoring-Details öffnen',
        'empty' => 'Es wurden noch keine Incidents aufgezeichnet.',
    ],
    'trend' => [
        'heading' => 'Zuverlässigkeitstrend',
        'description' => 'Aggregierte Verfügbarkeit der letzten sieben Tage.',
        'period' => 'Letzte 7 Tage',
        'no_data' => 'Der Trend erscheint, sobald tägliche Monitoringdaten vorhanden sind.',
        'uptime' => ':value % Verfügbarkeit',
    ],
    'recommended' => [
        'label' => 'Nächste empfohlene Aktion',
        'create' => 'Erstes Monitoring erstellen',
        'incidents' => 'Offene Incidents prüfen',
        'unknown' => 'Unbekannte oder veraltete Checks untersuchen',
        'notifications' => 'Fehlgeschlagene Zustellungen prüfen',
        'maintenance' => 'Wartungsfenster prüfen',
        'monitorings' => 'Monitoring-Liste prüfen',
    ],
    'empty' => [
        'title' => 'Deine Betriebsübersicht beginnt hier',
        'description' => 'Sobald du ein Monitoring hinzufügst, fasst diese Seite Gesundheit, Incidents, Wartung und die nächste Aktion zusammen.',
    ],
];
