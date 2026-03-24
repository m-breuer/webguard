<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'WebGuard - Uptime Monitoring, Alerts und öffentliche Statusseiten',
        'description' => 'WebGuard hilft Teams bei HTTP-, Ping-, Keyword- und Port-Checks mit sofortigen Benachrichtigungen, SSL-Ablaufkontrolle, Uptime-Analysen und transparenten Statusseiten.',
        'keywords' => 'Uptime Monitoring, Website Monitoring, Ping Monitoring, Keyword Monitoring, Port Monitoring, SSL Ablauf, Statusseite, Incident Benachrichtigung',
        'og_title' => 'WebGuard - Verfügbarkeit, Performance und Incidents früh erkennen',
        'og_description' => 'Erkennen Sie Ausfälle frühzeitig mit HTTP-, Ping-, Keyword- und Port-Überwachung, Multi-Channel-Benachrichtigungen, SSL-Prüfungen und klaren Uptime-Reports.',
    ],

    'nav' => [
        'aria' => 'Primäre Navigation',
        'logo_alt' => 'WebGuard Logo',
        'features' => 'Funktionen',
        'proof' => 'Vertrauen',
        'get_started' => 'Loslegen',
        'login' => 'Anmelden',
        'signup' => 'Konto erstellen',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'eyebrow' => 'Zuverlässiges Monitoring für kritische Services',
        'title' => 'Erkennen Sie Ausfälle, bevor es Ihre Nutzer tun.',
        'subtitle' => 'WebGuard gibt Ihrem Team sofortige Sichtbarkeit auf Uptime, Incidents, SSL-Risiken und Antwortzeiten über Websites, APIs und Infrastruktur-Endpunkte hinweg.',
        'primary_cta' => 'In Minuten Monitoring starten',
        'secondary_cta' => 'Beispiel-Statusseite ansehen',
        'tertiary_cta' => 'Sie haben bereits ein Konto?',
        'metrics' => [
            '1' => [
                'label' => 'Checks',
                'value' => '24/7 Überwachung mit kurzen Intervallen',
            ],
            '2' => [
                'label' => 'Alerts',
                'value' => 'Incident-Benachrichtigungen über mehrere Kanäle',
            ],
            '3' => [
                'label' => 'Vertrauen',
                'value' => 'DSGVO-fähige Prozesse und klare Reports',
            ],
        ],
    ],

    'feature_section' => [
        'eyebrow' => 'Umfassende Abdeckung',
        'title' => 'Alles, was Sie für zuverlässiges Monitoring brauchen',
        'subtitle' => 'Schaffen Sie Vertrauen mit breiten Protokoll-Checks, sofortigen Alerts, historischen Performance-Trends und transparentem Status für Ihre Kunden.',
    ],

    'features' => [
        'http' => [
            'badge' => 'Kernfunktion',
            'title' => 'HTTP Monitoring',
            'text' => 'Überwachen Sie API- und Website-Endpunkte mit Latenz- und Statuscode-Prüfung.',
        ],
        'ping' => [
            'badge' => 'Kernfunktion',
            'title' => 'Ping Monitoring',
            'text' => 'Verfolgen Sie Erreichbarkeit von Hosts und Stabilität des Netzwerkpfads mit schlanken Checks.',
        ],
        'keyword' => [
            'badge' => 'Kernfunktion',
            'title' => 'Keyword Monitoring',
            'text' => 'Prüfen Sie kritische Inhalte und erkennen Sie defekte Seiten oder Rendering-Regressionen.',
        ],
        'port' => [
            'badge' => 'Kernfunktion',
            'title' => 'Port Monitoring',
            'text' => 'Stellen Sie sicher, dass wichtige Service-Ports offen und erreichbar bleiben.',
        ],
        'notifications' => [
            'badge' => 'Alerts',
            'title' => 'Incident- und Status-Benachrichtigungen',
            'text' => 'Erhalten Sie Incident-Updates über mehrere Kanäle, damit Teams schnell und konsistent reagieren.',
        ],
        'ssl' => [
            'badge' => 'Sicherheit',
            'title' => 'SSL-Zertifikat Ablaufprüfung',
            'text' => 'Vermeiden Sie Zertifikatsprobleme mit klarer Ablaufkontrolle und frühzeitigen Warnungen.',
        ],
        'stats' => [
            'badge' => 'Insights',
            'title' => 'Antwortzeit- und Uptime-Statistiken',
            'text' => 'Analysieren Sie Trends, vergleichen Sie Monitor-Verhalten und berichten Sie über Zuverlässigkeit.',
        ],
        'multi_location' => [
            'badge' => 'Rollout',
            'title' => 'Monitoring aus mehreren Regionen',
            'text' => 'Regionenübergreifende Checks helfen, lokale Ausfälle zu isolieren und False Positives zu reduzieren.',
        ],
    ],

    'visuals' => [
        'eyebrow' => 'Produktvorschau',
        'title' => 'Ein fokussierter Workflow für Operations-Teams',
        'subtitle' => 'Vom Dashboard über Monitor-Details bis zu öffentlichen Status-Labels hält WebGuard den gesamten Incident-Kontext an einem Ort.',
        'previews' => [
            'dashboard' => [
                'title' => 'Dashboard-Übersicht',
                'text' => 'Verfolgen Sie den Zustand Ihrer Services auf einen Blick mit Uptime-Zusammenfassungen und Antwortzeit-Trends.',
                'alt' => 'Vorschau des WebGuard Dashboards mit Uptime-Trends und Service-Karten',
            ],
            'detail' => [
                'title' => 'Monitoring-Detailansicht',
                'text' => 'Analysieren Sie Spikes und Incidents mit Verlauf und Signalsicht pro Monitor.',
                'alt' => 'Vorschau der Monitoring-Detailansicht mit Incident-Zeitleiste und Antwortzeitmetriken',
            ],
            'public_status' => [
                'title' => 'Öffentliche Status-Labels',
                'text' => 'Teilen Sie Verfügbarkeits-Updates transparent in einem klaren, gebrandeten Format.',
                'alt' => 'Vorschau öffentlicher Status-Labels mit den Zuständen operational, degraded und incident',
            ],
        ],
    ],

    'workflow' => [
        'eyebrow' => 'So funktioniert es',
        'title' => 'Vom Setup bis zur Incident-Lösung in drei Schritten',
        'subtitle' => 'WebGuard ist für schnelles Onboarding optimiert und bietet zugleich die Kontrolle, die erfahrene Teams erwarten.',
        'steps' => [
            '1' => [
                'title' => 'Monitore erstellen',
                'text' => 'Legen Sie HTTP-, Ping-, Keyword- oder Port-Checks an und definieren Sie das Intervall.',
            ],
            '2' => [
                'title' => 'Alerts festlegen',
                'text' => 'Wählen Sie Benachrichtigungskanäle und erhalten Sie Incident-Updates ohne Verzögerung.',
            ],
            '3' => [
                'title' => 'Status teilen',
                'text' => 'Nutzen Sie Status-Labels und Uptime-Historie, um Zuverlässigkeit transparent zu kommunizieren.',
            ],
        ],
    ],

    'trust' => [
        'eyebrow' => 'Vertrauen und Transparenz',
        'title' => 'Entwickelt für Teams ohne Blind Spots',
        'subtitle' => 'WebGuard verbindet klare Reports, proaktive Absicherung und transparente Kommunikation.',
    ],

    'testimonial' => [
        'quote' => 'Wir haben drei getrennte Monitoring-Tools durch WebGuard ersetzt. Heute erkennen wir Incidents früher und veröffentlichen in Minuten klare Status-Updates für Kunden.',
        'author' => 'Lena Hoffmann',
        'role' => 'Platform Lead bei Northline Commerce',
    ],

    'case_study' => [
        'title' => 'Case Study: Schnellere Incident-Reaktion',
        'text' => 'Nach der Standardisierung von Checks und Benachrichtigungsregeln in WebGuard konnte ein SaaS-Supportteam Erkennungs- und Eskalationslücken in Lastspitzen deutlich reduzieren.',
        'metrics' => [
            '1' => [
                'label' => 'Downtime-Auswirkung',
                'value' => '-38 % in 90 Tagen',
            ],
            '2' => [
                'label' => 'Mean Time to Detect',
                'value' => 'Unter 2 Minuten',
            ],
            '3' => [
                'label' => 'Geschwindigkeit Status-Update',
                'value' => '2,4x schneller',
            ],
        ],
    ],

    'badges' => [
        'uptime' => [
            'title' => 'Uptime-Fokus',
            'text' => 'Monitoring-Architektur und Dashboard-Workflows sind auf availability-first Operations ausgelegt.',
        ],
        'gdpr' => [
            'title' => 'DSGVO-fähig',
            'text' => 'Datenschutzbewusste Verarbeitung und europäisch orientierte Compliance-Prinzipien unterstützen regulierte Teams.',
        ],
        'transparent' => [
            'title' => 'Transparentes Monitoring',
            'text' => 'Öffentliche Labels, historische Uptime-Kontexte und Open-Source-Komponenten fördern nachvollziehbaren Betrieb.',
        ],
    ],

    'final_cta' => [
        'title' => 'Erstellen Sie ein kostenloses Konto und starten Sie heute Ihren ersten Monitor.',
        'text' => 'Wechseln Sie von reaktivem Firefighting zu proaktiver Zuverlässigkeit mit einem Setup in Minuten statt Tagen.',
        'primary' => 'Kostenloses Konto erstellen',
        'secondary' => 'Beispiel-Statusseite ansehen',
    ],

    'guest_login' => [
        'title' => 'WebGuard erkunden',
        'text' => 'Neugierig, wie WebGuard funktioniert? Melden Sie sich mit unserem Gastkonto an und überwachen Sie beliebte Websites wie Google oder Twitter. Keine Registrierung erforderlich.',
        'button' => 'Als Gast anmelden',
    ],
];
