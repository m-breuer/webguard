<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'WebGuard - Kostenfreies Monitoring für Websites, APIs, Server, Ports und Cronjobs',
        'description' => 'WebGuard ist eine kostenfrei nutzbare Monitoring-Software für HTTP-, Ping-, Keyword-, Port-, Heartbeat-, Server-Zustand-, DNS-Eintrags-, SSL- und Domain-Ablaufprüfungen mit Integrationen, Widgets, konfigurierbaren Alerts, Wochenberichten, Uptime-Auswertungen und öffentlichen Statusseiten.',
        'keywords' => 'Kostenfreies Monitoring, Uptime Monitoring, Website Monitoring, Server-Zustand Monitoring, CPU Monitoring, RAM Monitoring, Speicher Monitoring, DNS-Eintragsmonitoring, erwartete HTTP-Statuscodes, Ping Monitoring, Keyword Monitoring, Port Monitoring, Heartbeat Monitoring, Cronjob Monitoring, Wochenbericht Monitoring, Monitoring Widget, REST API Monitoring, SSL Ablauf, Domain Ablauf Monitoring, Statusseite, Incident Benachrichtigung',
        'og_title' => 'WebGuard - Zuverlässigkeit transparent überwachen',
        'og_description' => 'Überwachen Sie Verfügbarkeit und Performance mit HTTP-, Ping-, Keyword-, Port-, Heartbeat-, Server-Zustand- und DNS-Checks, klaren Benachrichtigungen und transparenten Statusseiten.',
    ],

    'nav' => [
        'aria' => 'Primäre Navigation',
        'logo_alt' => 'WebGuard Logo',
        'features' => 'Funktionen',
        'feature_pages' => 'Feature-Seiten',
        'api_docs' => 'API-Doku',
        'proof' => 'Einordnung',
        'get_started' => 'Jetzt starten',
        'demo_access' => 'Demo-Zugang',
        'login' => 'Anmelden',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'eyebrow' => 'Kostenfrei nutzbare Monitoring-Software',
        'title' => 'WebGuard bietet professionelles Monitoring für Teams und Einzelprojekte.',
        'subtitle' => 'Überwachen Sie Websites, APIs, Jobs, Server, Zertifikate, Domains und kundenrelevanten Status aus einem klaren SaaS-Arbeitsbereich.',
        'primary_cta' => 'Jetzt starten',
        'secondary_cta' => 'Demo-Zugang',
        'api_cta' => 'API-Doku lesen',
        'legacy_coverage' => 'HTTP, Ping, Keyword, Port, Heartbeat, Server-Zustand, DNS, SSL und Domains',
        'legacy_badge_copy' => 'kompaktes SLA-Badge',
        'metrics' => [
            '1' => [
                'label' => 'Lizenz',
                'value' => 'Kostenfrei für alle nutzbar',
            ],
            '2' => [
                'label' => 'Abdeckung',
                'value' => 'HTTP, Ping, Keyword, Port, Heartbeat, Server-Zustand, DNS-Eintrag, SSL und Domains',
            ],
            '3' => [
                'label' => 'Betrieb',
                'value' => '24/7 Checks alle :interval',
            ],
        ],
    ],

    'feature_section' => [
        'eyebrow' => 'Umfassende Abdeckung',
        'title' => 'Alles, was Sie für zuverlässiges Monitoring brauchen',
        'subtitle' => 'Die Hauptseite zeigt die Grundlagen. Jede Funktion hat zusätzlich eine öffentliche Detailseite zur Bewertung vor dem Login.',
        'all_features_cta' => 'Alle Funktionen ansehen',
        'full_stack_title' => 'Alle WebGuard-Funktionen',
    ],

    'platform' => [
        'eyebrow' => 'Öffentliches Vertrauen und Integrationen',
        'title' => 'Statuskommunikation und API-Zugriff sind sichtbare öffentliche Flächen',
        'subtitle' => 'Public Labels, Badges, Statusseiten, Benachrichtigungen und die generierte Scribe-API-Referenz sind direkt von der Marketing-Seite verlinkt.',
    ],

    'features' => [
        'http' => [
            'badge' => 'Kernfunktion',
            'title' => 'HTTP Monitoring',
            'text' => 'Überwachen Sie API- und Website-Endpunkte mit Latenz, Methode, Headern, Body, Authentifizierung und Statuscode-Prüfung.',
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
        'server_health' => [
            'badge' => 'Server',
            'title' => 'Server-Zustand-Monitoring',
            'text' => 'Nehmen Sie CPU-, RAM-, Speicher-, Load- und Uptime-Reports entgegen und setzen Sie pro Monitor eigene Schwellen, bevor Reports als down gelten.',
        ],
        'dns_record' => [
            'badge' => 'DNS',
            'title' => 'DNS-Eintragsmonitoring',
            'text' => 'Überwachen Sie erwartete A-, AAAA-, CNAME-, MX-, TXT-, NS-, SOA- und CAA-Einträge und alarmieren Sie bei gemeldeten Abweichungen.',
        ],
        'notifications' => [
            'badge' => 'Alerts',
            'title' => 'Incident- und Status-Benachrichtigungen',
            'text' => 'Erhalten Sie Incident-, Recovery-, SSL- und Domain-Ablaufupdates über Slack, Telegram, Discord, Microsoft Teams, Webhooks und In-App-Benachrichtigungen.',
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
            'text' => 'Überwachen Sie den Ablauf wichtiger Domains und versenden Sie proaktive Verlängerungswarnungen, bevor fehlende Verlängerungen zu Ausfällen werden.',
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
        'public_status_pages' => [
            'badge' => 'Transparenz',
            'title' => 'Öffentliche Statusseiten',
            'text' => 'Veröffentlichen Sie komponentenbasierte Statusseiten mit aktuellen Incidents, manuellen Updates, E-Mail-Abos und aktiven oder geplanten Wartungsfenstern.',
        ],
        'embeddable_widget' => [
            'badge' => 'Embed',
            'title' => 'Einbettbares Status-Widget',
            'text' => 'Binden Sie ein schlankes JavaScript-Widget in externe Websites oder Dashboards ein, damit Besucher den Live-Status dort sehen, wo sie arbeiten.',
        ],
        'rest_api' => [
            'badge' => 'API',
            'title' => 'REST API und Integrationen',
            'text' => 'Nutzen Sie tokenbasierten API-Zugriff und die API-Referenz, um Monitoring-Daten mit externen Tools, Automationen und Reports zu verbinden.',
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
                'text' => 'Teilen Sie Verfügbarkeits-, Wartungs- und Incident-Updates transparent in einem klaren, gebrandeten Format.',
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
                'text' => 'Legen Sie HTTP-, Ping-, Keyword-, Port-, Heartbeat-, Server-Zustand- oder DNS-Eintragschecks an und definieren Sie das Intervall.',
            ],
            '2' => [
                'title' => 'Alerts festlegen',
                'text' => 'Wählen Sie Slack, Telegram, Discord, Microsoft Teams, Webhook- oder In-App-Kanäle und erhalten Sie Incident-Updates ohne Verzögerung.',
            ],
            '3' => [
                'title' => 'Status teilen',
                'text' => 'Nutzen Sie Status-Labels, öffentliche Statusseiten, Widgets und Uptime-Historie, um Zuverlässigkeit transparent zu kommunizieren.',
            ],
        ],
    ],

    'trust' => [
        'eyebrow' => 'Grundprinzipien',
        'title' => 'Transparente, kostenfreie Software für verlässliches Monitoring',
        'subtitle' => 'WebGuard wird als allgemein nutzbare Software bereitgestellt. Der Fokus liegt auf stabiler Funktion, klarer Nachvollziehbarkeit und kostenfreiem Zugang statt Vertrieb.',
    ],

    'testimonial' => [
        'quote' => 'Die Kombination aus Regionen, 5-Minuten-Intervall und klaren Alerts hilft dabei, Störungen schneller zu erkennen und sauber einzuordnen.',
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
                'value' => 'HTTP, Ping, Keyword, Port, Heartbeat, Server-Zustand, DNS-Eintrag',
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
        'text' => 'Die Software steht ohne Kaufmodell zur Verfügung. Login oder Demo-Zugang reichen aus, um die zentralen Funktionen auszuprobieren.',
        'primary' => 'Jetzt starten',
        'secondary' => 'Demo-Zugang öffnen',
    ],

    'guest_login' => [
        'title' => 'WebGuard ausprobieren',
        'text' => 'Melden Sie sich mit dem Demo-Benutzer an und erkunden Sie Dashboards, Checks und Benachrichtigungen direkt im laufenden Beispiel. Keine Registrierung erforderlich.',
        'button' => 'Als Demo-Benutzer anmelden',
    ],
];
