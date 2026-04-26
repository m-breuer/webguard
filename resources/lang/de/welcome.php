<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'WebGuard - Kostenfreies Monitoring für Websites, APIs, Server, Ports und Cronjobs',
        'description' => 'WebGuard ist eine kostenfrei nutzbare Monitoring-Software für HTTP-, Ping-, Keyword-, Port- und Heartbeat-Checks mit erwarteten HTTP-Statusbereichen, Wochenberichten, Benachrichtigungen, SSL-Ablaufkontrolle, Uptime-Auswertungen und öffentlichen Statusseiten.',
        'keywords' => 'Kostenfreies Monitoring, Uptime Monitoring, Website Monitoring, erwartete HTTP-Statuscodes, Ping Monitoring, Keyword Monitoring, Port Monitoring, Heartbeat Monitoring, Cronjob Monitoring, Wochenbericht Monitoring, SSL Ablauf, Statusseite, Incident Benachrichtigung',
        'og_title' => 'WebGuard - Zuverlässigkeit transparent überwachen',
        'og_description' => 'Überwachen Sie Verfügbarkeit und Performance mit HTTP-, Ping-, Keyword-, Port- und Heartbeat-Checks, klaren Benachrichtigungen und nachvollziehbaren Uptime-Reports.',
    ],

    'nav' => [
        'aria' => 'Primäre Navigation',
        'logo_alt' => 'WebGuard Logo',
        'features' => 'Funktionen',
        'proof' => 'Einordnung',
        'get_started' => 'Kostenfrei nutzen',
        'login' => 'Anmelden',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'eyebrow' => 'Kostenfrei nutzbare Monitoring-Software',
        'title' => 'WebGuard bietet professionelles Monitoring für Teams und Einzelprojekte.',
        'subtitle' => 'Die Plattform unterstützt die zuverlässige Überwachung von Services und Infrastruktur und kann ohne Lizenzkosten genutzt werden.',
        'primary_cta' => 'Kostenfrei starten',
        'secondary_cta' => 'Gastzugang nutzen',
        'metrics' => [
            '1' => [
                'label' => 'Lizenz',
                'value' => 'Kostenfrei für alle nutzbar',
            ],
            '2' => [
                'label' => 'Abdeckung',
                'value' => 'HTTP, Ping, Keyword, Port, Heartbeat und Berichte',
            ],
            '3' => [
                'label' => 'Betrieb',
                'value' => '24/7 Checks mit flexiblen Intervallen',
            ],
        ],
    ],

    'feature_section' => [
        'eyebrow' => 'Umfassende Abdeckung',
        'title' => 'Alles, was Sie für zuverlässiges Monitoring brauchen',
        'subtitle' => 'WebGuard bündelt Checks, Benachrichtigungen und Verlaufsdaten in einem klaren, alltagstauglichen Workflow.',
    ],

    'features' => [
        'http' => [
            'badge' => 'Kernfunktion',
            'title' => 'HTTP Monitoring',
            'text' => 'Überwachen Sie API- und Website-Endpunkte mit Latenz- und Statuscode-Prüfung.',
        ],
        'http_expectations' => [
            'badge' => 'Kontrolle',
            'title' => 'Erwartete HTTP-Statusbereiche',
            'text' => 'Definieren Sie akzeptierte Statuscodes oder Bereiche wie 200-299, 301 und 302 pro HTTP- oder Keyword-Monitor.',
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
        'heartbeat' => [
            'badge' => 'Cronjobs',
            'title' => 'Heartbeat Monitoring',
            'text' => 'Überwachen Sie Cronjobs, Worker und Hintergrundprozesse über private Ping-URLs und erwartete Intervalle.',
        ],
        'notifications' => [
            'badge' => 'Alerts',
            'title' => 'Incident- und Status-Benachrichtigungen',
            'text' => 'Erhalten Sie Incident-Updates über mehrere Kanäle, damit Reaktionen schnell und abgestimmt erfolgen.',
        ],
        'weekly_digest' => [
            'badge' => 'Berichte',
            'title' => 'Wöchentlicher Monitoring-Bericht',
            'text' => 'Versenden Sie wöchentliche E-Mail-Zusammenfassungen mit Uptime, Incidents, längster Downtime sowie SSL- oder Domain-Ablaufwarnungen.',
        ],
        'ssl' => [
            'badge' => 'Sicherheit',
            'title' => 'SSL-Zertifikat Ablaufprüfung',
            'text' => 'Vermeiden Sie Zertifikatsprobleme mit klarer Ablaufkontrolle und frühzeitigen Warnungen.',
        ],
        'domain_expiration' => [
            'badge' => 'Eigentum',
            'title' => 'Domain-Ablaufprüfungen',
            'text' => 'Überwachen Sie den Ablauf wichtiger Domains, bevor fehlende Verlängerungen zu Ausfällen werden.',
        ],
        'stats' => [
            'badge' => 'Insights',
            'title' => 'Antwortzeit- und Uptime-Statistiken',
            'text' => 'Analysieren Sie Trends, vergleichen Sie Monitor-Verhalten und berichten Sie über Zuverlässigkeit.',
        ],
        'multi_location' => [
            'badge' => 'Verteilung',
            'title' => 'Monitoring aus mehreren Regionen',
            'text' => 'Regionenübergreifende Checks helfen, lokale Ausfälle zu isolieren und Fehlalarme zu reduzieren.',
        ],
    ],

    'visuals' => [
        'eyebrow' => 'Produktüberblick',
        'title' => 'Ein klarer Workflow für Betrieb und Monitoring',
        'subtitle' => 'Vom Dashboard über Monitor-Details bis zu öffentlichen Status-Labels bleibt der gesamte Incident-Kontext an einem Ort.',
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
        'subtitle' => 'Der Einstieg ist bewusst schlank gehalten und bietet zugleich genug Tiefe für den laufenden Betrieb.',
        'steps' => [
            '1' => [
                'title' => 'Monitore erstellen',
                'text' => 'Legen Sie HTTP-, Ping-, Keyword-, Port- oder Heartbeat-Checks an und definieren Sie das Intervall.',
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
        'eyebrow' => 'Grundprinzipien',
        'title' => 'Transparente, kostenfreie Software für verlässliches Monitoring',
        'subtitle' => 'WebGuard wird als allgemein nutzbare Software bereitgestellt. Der Fokus liegt auf stabiler Funktion, klarer Nachvollziehbarkeit und kostenfreiem Zugang statt Vertrieb.',
    ],

    'testimonial' => [
        'quote' => 'Die Kombination aus Regionen, flexiblen Intervallen und klaren Alerts hilft dabei, Störungen schneller zu erkennen und sauber einzuordnen.',
    ],

    'case_study' => [
        'title' => 'Beispielkonfiguration für typische Services',
        'text' => 'Eine robuste Basiskonfiguration nutzt feste Intervalle, mehrere Regionen und klare Alarmregeln pro Endpoint. So lassen sich Ausfälle früh erkennen und schneller als lokal oder global einstufen.',
        'metrics' => [
            '1' => [
                'label' => 'Regionen pro Check',
                'value' => 'DE',
            ],
            '2' => [
                'label' => 'Standard-Intervall',
                'value' => '1 Minute|:count Minuten',
            ],
            '3' => [
                'label' => 'Empfohlene Monitor-Typen',
                'value' => 'HTTP, Ping, Keyword, Port, Heartbeat',
            ],
        ],
    ],

    'badges' => [
        'uptime' => [
            'title' => 'Stabilitätsfokus',
            'text' => 'Die Monitoring-Architektur ist auf belastbare Erreichbarkeitschecks und schnelle Incident-Einordnung ausgerichtet.',
        ],
        'transparent' => [
            'title' => 'Offen und nachvollziehbar',
            'text' => 'Öffentliche Statusinformationen und klare Verläufe machen den Betrieb im Alltag transparent.',
        ],
    ],

    'final_cta' => [
        'title' => 'WebGuard kostenfrei nutzen',
        'text' => 'Die Software steht ohne Kaufmodell zur Verfügung. Login oder Gastzugang reichen aus, um die zentralen Funktionen auszuprobieren.',
        'primary' => 'Zum Login',
        'secondary' => 'Gastzugang öffnen',
    ],

    'guest_login' => [
        'title' => 'WebGuard ausprobieren',
        'text' => 'Melden Sie sich mit dem Gastkonto an und erkunden Sie Dashboards, Checks und Benachrichtigungen direkt im laufenden Beispiel. Keine Registrierung erforderlich.',
        'button' => 'Als Gast anmelden',
    ],
];
