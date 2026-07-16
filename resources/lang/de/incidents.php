<?php

declare(strict_types=1);

return [
    'analytics' => [
        'title' => 'Vorfallanalysen',
        'description' => 'Analysieren Sie Häufigkeit, Auswirkungen, Wiederholungen und Wiederherstellungszeit Ihrer Überwachungen.',
        'link' => 'Vorfallanalysen',
        'filters' => [
            'period' => 'Zeitraum',
            'type' => 'Typ',
            'severity' => 'Schweregrad',
            'customer_impact' => 'Kundenauswirkung',
            'affected_service' => 'Betroffener Dienst',
            'all' => 'Alle',
            'days_30' => 'Letzte 30 Tage',
            'days_90' => 'Letzte 90 Tage',
            'days_365' => 'Letzte 365 Tage',
            'apply' => 'Filter anwenden',
        ],
        'metrics' => [
            'total' => 'Vorfälle',
            'resolved' => 'Behoben',
            'open' => 'Offen',
            'mttr' => 'Durchschnittliche MTTR',
            'minutes' => ':value Min.',
            'not_available' => 'n/a',
        ],
        'sections' => [
            'by_type' => 'Nach Typ',
            'by_severity' => 'Nach Schweregrad',
            'by_impact' => 'Nach Kundenauswirkung',
            'by_service' => 'Nach betroffenem Dienst',
            'recurrence' => 'Wiederkehrende Dienste',
            'recent' => 'Vorfälle im ausgewählten Zeitraum',
        ],
        'definitions' => 'Die Vorfallanzahl umfasst im Zeitraum eröffnete Vorfälle. MTTR ist die durchschnittliche Zeit zwischen Ausfall und Wiederherstellung für behobene Vorfälle. Wiederkehrend sind Dienste mit mehr als einem Vorfall.',
        'unclassified' => 'Nicht klassifiziert',
        'empty' => 'Keine Vorfälle entsprechen den ausgewählten Filtern.',
    ],
    'types' => [
        'availability' => 'Verfügbarkeit',
        'performance' => 'Performance',
        'security' => 'Sicherheit',
        'dependency' => 'Abhängigkeit',
        'configuration' => 'Konfiguration',
        'other' => 'Sonstiges',
        'unclassified' => 'Nicht klassifiziert',
    ],
    'severities' => [
        'low' => 'Niedrig',
        'medium' => 'Mittel',
        'high' => 'Hoch',
        'critical' => 'Kritisch',
        'unclassified' => 'Nicht klassifiziert',
    ],
    'customer_impacts' => [
        'none' => 'Keine',
        'degraded' => 'Beeinträchtigt',
        'outage' => 'Ausfall',
        'unknown' => 'Unbekannt',
        'unclassified' => 'Nicht klassifiziert',
    ],
    'contributing_categories' => [
        'code' => 'Code',
        'infrastructure' => 'Infrastruktur',
        'dependency' => 'Abhängigkeit',
        'configuration' => 'Konfiguration',
        'process' => 'Prozess',
        'unknown' => 'Unbekannt',
    ],
];
