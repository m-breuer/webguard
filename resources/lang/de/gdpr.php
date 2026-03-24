<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Datenschutzerklärung | WebGuard',
        'description' => 'Datenschutzerklärung gemäß DSGVO für WebGuard.',
        'keywords' => 'datenschutz, dsgvo, privacy policy, webguard',
        'og_title' => 'WebGuard Datenschutzerklärung',
        'og_description' => 'Wie WebGuard personenbezogene Daten nach DSGVO verarbeitet.',
    ],
    'footer_link' => 'Datenschutzerklärung',
    'hero' => [
        'eyebrow' => 'Datenschutz',
        'title' => 'Datenschutzerklärung',
        'subtitle' => 'Informationen zur Verarbeitung personenbezogener Daten gemäß DSGVO.',
        'last_updated' => 'Letzte Aktualisierung: :date',
        'last_updated_date' => '24. März 2026',
    ],
    'sections' => [
        'controller' => [
            'title' => '1. Verantwortliche Stelle',
            'lead' => 'Für die Datenverarbeitung auf dieser Plattform ist der nachfolgend genannte Betreiber verantwortlich.',
        ],
        'data_categories' => [
            'title' => '2. Kategorien verarbeiteter personenbezogener Daten',
            'lead' => 'Je nach Nutzung verarbeitet WebGuard insbesondere folgende Datenkategorien:',
            'items' => [
                'Konto- und Profildaten (Name, E-Mail, Login-Metadaten, Spracheinstellung, Theme-Einstellungen).',
                'Monitoring-Konfigurationsdaten (Ziel-URLs, Hostnamen, Ports, Request-Methoden, Zeitpläne, Alert-Konfiguration).',
                'Monitoring- und Nutzungsprotokolle (Statusänderungen, Antwortzeiten, Benachrichtigungsstatus, revisionsrelevante Aktionen).',
                'Benachrichtigungskanal-Einstellungen und Versandmetadaten (E-Mail, Slack, Teams, WhatsApp, Discord und ähnliche Kanäle, sofern konfiguriert).',
                'Technische Metadaten (IP-Adresse, User Agent, Zeitstempel, Session-IDs, API-Zugriffsdaten).',
                'SSL/TLS-Monitoringdaten (Zertifikatslaufzeiten, Zertifikatsmetadaten, zugehörige Warnereignisse).',
            ],
        ],
        'purposes_legal_basis' => [
            'title' => '3. Zwecke und Rechtsgrundlagen der Verarbeitung',
            'lead' => 'Daten werden nur verarbeitet, soweit dies für den sicheren und zuverlässigen Betrieb von WebGuard erforderlich ist.',
            'purposes_title' => 'Verarbeitungszwecke',
            'purposes' => [
                'Bereitstellung und Betrieb der Monitoring-Plattform.',
                'Uptime- und Vorfallanalyse, Fehlerbehebung sowie Qualitätsverbesserung.',
                'Sicherheitsfunktionen, Missbrauchsprävention, Betrugserkennung und Schutz der Systemintegrität.',
                'Versand von Monitoring-Benachrichtigungen und servicebezogener Kommunikation.',
            ],
            'legal_basis_title' => 'Rechtsgrundlagen nach Art. 6 DSGVO',
            'legal_basis' => [
                'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung und vorvertragliche Maßnahmen).',
                'Art. 6 Abs. 1 lit. c DSGVO (Erfüllung rechtlicher Verpflichtungen, soweit einschlägig).',
                'Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse am sicheren und stabilen Plattformbetrieb).',
                'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung, soweit gesondert eingeholt).',
            ],
        ],
        'third_party' => [
            'title' => '4. Einsatz von Drittanbietern und Auftragsverarbeitern',
            'lead' => 'WebGuard kann externe Dienstleister einsetzen, um Kernfunktionen und Integrationen bereitzustellen.',
            'items' => [
                'Hosting- und Infrastrukturanbieter (Compute, Storage, Netzwerk, Backups).',
                'Monitoring- und Verfügbarkeitsprüfungs-Infrastruktur (einschließlich SSL- und Endpoint-Prüfungen).',
                'Benachrichtigungsanbieter und Integrationen (z. B. Teams, Slack, WhatsApp, Discord, E-Mail-Versanddienste).',
                'Betriebliche Werkzeuge, die für eine sichere und zuverlässige Leistungserbringung erforderlich sind.',
            ],
            'note' => 'Soweit erforderlich werden Auftragsverarbeitungsverträge nach Art. 28 DSGVO geschlossen.',
        ],
        'cookies' => [
            'title' => '5. Cookies und Wahlmöglichkeiten',
            'lead' => 'WebGuard verwendet technisch notwendige Cookies für Login- und Sitzungsfunktionen.',
            'items' => [
                'Session-Cookies für Authentifizierung und sichere Kontonutzung.',
                'Präferenz-Cookies (z. B. Spracheinstellung) zur Verbesserung der Nutzbarkeit.',
                'Optionale Analyse- oder Tracking-Cookies nur bei expliziter Aktivierung und rechtlicher Zulässigkeit.',
            ],
            'options' => 'Sie können Cookies über Ihren Browser blockieren oder löschen. Das Blockieren erforderlicher Cookies kann die Funktionalität einschränken.',
        ],
        'rights' => [
            'title' => '6. Ihre Rechte nach DSGVO',
            'lead' => 'Ihnen stehen im Rahmen der gesetzlichen Voraussetzungen insbesondere folgende Rechte zu:',
            'items' => [
                'Auskunftsrecht (Art. 15 DSGVO).',
                'Recht auf Berichtigung (Art. 16 DSGVO).',
                'Recht auf Löschung (Art. 17 DSGVO).',
                'Recht auf Einschränkung der Verarbeitung (Art. 18 DSGVO).',
                'Recht auf Datenübertragbarkeit (Art. 20 DSGVO).',
                'Widerspruchsrecht (Art. 21 DSGVO).',
                'Recht auf Widerruf einer Einwilligung mit Wirkung für die Zukunft (Art. 7 Abs. 3 DSGVO).',
                'Beschwerderecht bei einer Aufsichtsbehörde (Art. 77 DSGVO).',
            ],
        ],
        'retention' => [
            'title' => '7. Speicherdauer',
            'lead' => 'Personenbezogene Daten werden nur so lange gespeichert, wie dies für vertragliche, gesetzliche und betriebliche Zwecke erforderlich ist. Danach werden Daten gelöscht oder anonymisiert.',
        ],
        'security' => [
            'title' => '8. Sicherheitsmaßnahmen',
            'lead' => 'WebGuard setzt angemessene technische und organisatorische Maßnahmen ein, um personenbezogene Daten vor unbefugtem Zugriff, Verlust oder Manipulation zu schützen.',
        ],
        'contact' => [
            'title' => '9. Kontakt zu Datenschutzanfragen',
            'lead' => 'Bei Fragen zum Datenschutz kontaktieren Sie den Betreiber über die untenstehenden Kontaktdaten.',
            'complaint' => 'Wenn Sie der Ansicht sind, dass Ihre Datenschutzrechte verletzt wurden, können Sie sich an eine zuständige Aufsichtsbehörde wenden.',
        ],
    ],
    'fields' => [
        'operator_name' => 'Betreiber',
        'address' => 'Anschrift',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
    ],
];
