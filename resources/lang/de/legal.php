<?php

declare(strict_types=1);

return [
    'terms_of_use' => [
        'seo' => [
            'title' => 'Nutzungsbedingungen | WebGuard',
            'description' => 'Nutzungsbedingungen für die nicht-kommerzielle WebGuard-Demoplattform.',
            'keywords' => 'webguard nutzungsbedingungen, rechtliche bedingungen, demo monitoring',
            'og_title' => 'WebGuard Nutzungsbedingungen',
            'og_description' => 'Rechtliche Bedingungen für die Nutzung der WebGuard-Demoplattform.',
        ],
        'hero' => [
            'eyebrow' => 'Rechtliche Bedingungen',
            'title' => 'Nutzungsbedingungen',
            'subtitle' => 'Regeln für die Nutzung der WebGuard-Demoplattform.',
        ],
        'sections' => [
            'scope' => [
                'title' => 'Leistungsumfang',
                'intro' => 'WebGuard stellt derzeit insbesondere folgende Funktionen bereit:',
                'items' => [
                    'Monitoring-Typen HTTP, Ping, Keyword und Port für konfigurierte Ziele.',
                    'SSL-Zertifikatsprüfungen inkl. Ablaufwarnungen für geeignete Endpunkte.',
                    'Status- und Vorfallauswertung, Antwortzeiten sowie Uptime-/Downtime-Visualisierungen.',
                    'Öffentliche Status-Labels (Public Label), sofern vom Nutzer aktiviert.',
                    'E-Mail-Benachrichtigungen zu Statusänderungen, SSL-Abläufen und ungelesenen Meldungen.',
                    'Tokenbasierter API-Zugriff für freigegebene Endpunkte.',
                    'Optionale Anmeldung über GitHub sowie Demo-/Gastzugang.',
                ],
            ],
            'obligations' => [
                'title' => 'Pflichten der Nutzer',
                'intro' => 'Nutzer verpflichten sich insbesondere zu folgendem Verhalten:',
                'items' => [
                    'Bereitstellung korrekter und vollständiger Angaben.',
                    'Monitoring nur für Systeme, Endpunkte und Domains, für die eine Berechtigung vorliegt.',
                    'Sorgfältiger Umgang mit Zugangsdaten, API-Tokens und optional hinterlegten Authentifizierungsdaten.',
                    'Ausschließlich rechtmäßige Nutzung von WebGuard.',
                    'Keine missbräuchliche Nutzung, Störung oder übermäßige Belastung des Dienstes (z. B. durch exzessive automatisierte Zugriffe).',
                    'Keine unbefugten Zugriffsversuche auf Systeme oder Daten.',
                    'Bei aktivierten Public Labels sicherstellen, dass keine vertraulichen Informationen in Monitoring-Namen oder Zieladressen öffentlich exponiert werden.',
                ],
            ],
            'liability' => [
                'title' => 'Haftungsbeschränkung',
                'body' => 'WebGuard wird "wie besehen" und "wie verfügbar" bereitgestellt. Es besteht kein Anspruch auf unterbrechungsfreie Verfügbarkeit, bestimmte Reaktionszeiten oder lückenlose Alarmierung. Monitoringdaten können durch externe Faktoren (z. B. Netzwerkstörungen, Drittanbieter-Ausfälle, Zielsystemkonfigurationen) verzögert oder ungenau sein. Eine Haftung für mittelbare Schäden, Folgeschäden, entgangenen Gewinn sowie Schäden durch Ausfälle oder Fehlinterpretation von Monitoringdaten ist ausgeschlossen, soweit gesetzlich zulässig. Zwingende gesetzliche Haftung bleibt unberührt.',
            ],
            'termination' => [
                'title' => 'Beendigung und Sperrung',
                'body' => 'Konten können bei Missbrauch, Rechtsverstößen, Sicherheitsrisiken oder störendem Verhalten vorübergehend gesperrt oder dauerhaft geschlossen werden. Nutzer können die Nutzung jederzeit beenden und ihr Konto löschen. Als nicht-kommerzielles Demonstrationssystem kann WebGuard Funktionen jederzeit anpassen, einschränken oder einstellen.',
            ],
            'governing_law' => [
                'title' => 'Anwendbares Recht und Gerichtsstand',
                'body' => 'Es gilt das Recht der Bundesrepublik Deutschland. Sofern Sie Verbraucher sind, bleiben zwingende Verbraucherschutzvorschriften Ihres Aufenthaltsstaates unberührt. Soweit gesetzlich zulässig, ist Gerichtsstand der Sitz des Betreibers.',
            ],
            'contact' => [
                'title' => 'Kontakt',
                'body' => 'Für rechtliche Anfragen oder Support kontaktieren Sie uns unter:',
            ],
            'non_commercial' => [
                'title' => 'Nicht-kommerzieller Hinweis',
                'body' => 'WebGuard wird derzeit ausschließlich als nicht-kommerzielles Demonstrations- und Testsystem betrieben. Es besteht insbesondere kein Anspruch auf Vergütung, Service Level Agreements (SLA) oder kommerziellen Support.',
            ],
        ],
        'footer_link' => 'Nutzungsbedingungen',
    ],
    'privacy_policy' => [
        'seo' => [
            'title' => 'DSGVO-Datenschutzhinweise | WebGuard',
            'description' => 'Datenschutz- und DSGVO-Informationen für WebGuard.',
            'keywords' => 'webguard dsgvo, datenschutz, datenschutzhinweise',
            'og_title' => 'WebGuard DSGVO-Datenschutzhinweise',
            'og_description' => 'Informationen zur Verarbeitung personenbezogener Daten bei WebGuard.',
        ],
        'hero' => [
            'eyebrow' => 'Datenschutz',
            'title' => 'DSGVO-Datenschutzhinweise',
            'subtitle' => 'Wie personenbezogene Daten in der WebGuard-Demoumgebung verarbeitet werden.',
        ],
        'sections' => [
            'overview' => [
                'title' => 'Überblick',
                'body' => 'WebGuard wird derzeit als nicht-kommerzielles Demonstrationsprojekt betrieben. Personenbezogene Daten werden nur verarbeitet, soweit dies für Kontozugang, Monitoring-Funktionen und betriebliche Kommunikation erforderlich ist. Für Datenschutzanfragen nutzen Sie bitte die geschützte Kontaktanzeige unten.',
            ],
        ],
    ],
    'footer' => [
        'content' => 'Alle Rechte vorbehalten.',
    ],
];
