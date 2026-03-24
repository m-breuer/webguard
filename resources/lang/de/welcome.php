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
        'get_started' => 'Projekt testen',
        'login' => 'Anmelden',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'eyebrow' => 'Eigenes Monitoring-Projekt',
        'title' => 'WebGuard ist mein persönlicher Service für zuverlässiges Monitoring.',
        'subtitle' => 'Ich habe WebGuard gebaut, um meine eigenen Websites und APIs zu überwachen. Diese Instanz stelle ich öffentlich bereit, damit Sie die Funktionen unverbindlich testen können.',
        'primary_cta' => 'In Minuten Monitoring starten',
        'secondary_cta' => 'Demozugang öffnen',
        'metrics' => [
            '1' => [
                'label' => 'Setup',
                'value' => 'Eigenes Projekt für tägliches Monitoring',
            ],
            '2' => [
                'label' => 'Intervall',
                'value' => '24/7 Checks mit flexiblen Abständen',
            ],
            '3' => [
                'label' => 'Regionen',
                'value' => 'Auswahl pro Monitor für bessere Einordnung',
            ],
        ],
    ],

    'feature_section' => [
        'eyebrow' => 'Umfassende Abdeckung',
        'title' => 'Alles, was Sie für zuverlässiges Monitoring brauchen',
        'subtitle' => 'Die Funktionen nutze ich selbst im Alltag: klare Checks, sinnvolle Alerts und verlässliche Verlaufsdaten an einem Ort.',
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
        'eyebrow' => 'Projektkontext',
        'title' => 'Warum ich WebGuard gebaut habe',
        'subtitle' => 'WebGuard ist als eigenes Monitoring-Projekt entstanden. Ich stelle den Service hier öffentlich zum Testen bereit, ohne Kaufmodell oder Vertrieb.',
    ],

    'testimonial' => [
        'quote' => 'Die Regionsauswahl und die flexiblen Intervalle sind für mich der größte Mehrwert. Ich sehe schnell, ob ein Problem regional ist, und meine Monitorings laufen dadurch deutlich zuverlässiger.',
    ],

    'case_study' => [
        'title' => 'Praxis-Setup aus meinem Alltag',
        'text' => 'Für meine eigenen Services nutze ich pro Endpoint feste Intervalle, mehrere Regionen und klare Alarmregeln. So erkenne ich Ausfälle früh und kann schneller einschätzen, ob sie lokal oder global auftreten.',
        'metrics' => [
            '1' => [
                'label' => 'Regionen pro Check',
                'value' => 'EU + US',
            ],
            '2' => [
                'label' => 'Standard-Intervall',
                'value' => '60 Sekunden',
            ],
            '3' => [
                'label' => 'Aktive Monitore',
                'value' => 'HTTP, Ping, Keyword, Port',
            ],
        ],
    ],

    'badges' => [
        'uptime' => [
            'title' => 'Uptime-Fokus',
            'text' => 'Die Monitoring-Architektur ist auf stabile Erreichbarkeitschecks und schnelle Einordnung von Ausfällen ausgerichtet.',
        ],
        'transparent' => [
            'title' => 'Transparentes Monitoring',
            'text' => 'Öffentliche Statusinformationen und nachvollziehbare Verläufe zeigen offen, wie der Service im Alltag läuft.',
        ],
    ],

    'final_cta' => [
        'title' => 'WebGuard unverbindlich testen',
        'text' => 'Hier findet kein Verkauf statt. Sie können den Service direkt per Login oder über den Demozugang ausprobieren.',
        'primary' => 'Zum Login',
        'secondary' => 'Demozugang starten',
    ],

    'guest_login' => [
        'title' => 'WebGuard erkunden',
        'text' => 'Neugierig, wie WebGuard funktioniert? Melden Sie sich mit unserem Gastkonto an und überwachen Sie beliebte Websites wie Google oder Twitter. Keine Registrierung erforderlich.',
        'button' => 'Als Gast anmelden',
    ],
];
