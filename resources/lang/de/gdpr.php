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
                'Konto- und Profildaten (Name, E-Mail, Passwort-Hash, Rolle, Spracheinstellung, Theme-Einstellungen, ggf. Avatar).',
                'Authentifizierungs- und Sitzungsdaten (Login-Zeitpunkte, Session-IDs, IP-Adresse, User-Agent, technisch erforderliche Session-/CSRF-Metadaten).',
                'Einwilligungs- und Nachweisdaten (Zeitstempel zur Zustimmung zu Nutzungsbedingungen und Datenschutzerklärung).',
                'Monitoring-Konfigurationsdaten (Name, Monitoring-Typ, Zieladresse, Port, Keyword, HTTP-Methode, Header/Body, optionale Zugangsdaten, bevorzugter Standort, Wartungsfenster, Public-Label-Einstellung).',
                'Monitoring-Ergebnisdaten (Status, HTTP-Statuscodes, Antwortzeiten, SSL/TLS-Zertifikatsdaten, Vorfälle, Tagesaggregate für Uptime/Downtime).',
                'Benachrichtigungsdaten (E-Mail-Benachrichtigungen, Versandstatus, Lesezustand von Benachrichtigungen).',
                'API-Daten (persönliche Access-Tokens, protokollierte API-Routen, Zeitstempel).',
                'Optional bei GitHub-Login: GitHub-ID, OAuth-Token/Refresh-Token, Profilbild-URL und verknüpfte E-Mail-Adresse.',
            ],
        ],
        'purposes_legal_basis' => [
            'title' => '3. Zwecke und Rechtsgrundlagen der Verarbeitung',
            'lead' => 'Daten werden nur verarbeitet, soweit dies für den sicheren und zuverlässigen Betrieb von WebGuard erforderlich ist.',
            'purposes_title' => 'Verarbeitungszwecke',
            'purposes' => [
                'Bereitstellung von Registrierung, Login (inkl. optionalem GitHub-Login), Kontoverwaltung und Authentifizierung.',
                'Durchführung von Monitoring-Prüfungen (HTTP, Ping, Keyword, Port), Incident-Erkennung sowie Berechnung von Uptime- und Performance-Auswertungen.',
                'Versand servicebezogener E-Mails (z. B. Verifikation, Passwort-Reset, Statusänderungen, SSL-Ablaufwarnungen, Erinnerungen zu ungelesenen Benachrichtigungen).',
                'Bereitstellung und Absicherung der API (Token-Verwaltung, Missbrauchsschutz, Nutzungsprotokollierung).',
                'Sicherheits- und Betriebszwecke (Fehleranalyse, Störungsbehebung, Integritätsschutz).',
            ],
            'legal_basis_title' => 'Rechtsgrundlagen nach Art. 6 DSGVO',
            'legal_basis' => [
                'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung und vorvertragliche Maßnahmen).',
                'Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse am sicheren und stabilen Plattformbetrieb).',
                'Art. 6 Abs. 1 lit. c DSGVO (Erfüllung rechtlicher Verpflichtungen, soweit einschlägig).',
                'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung, soweit gesondert eingeholt).',
            ],
        ],
        'third_party' => [
            'title' => '4. Einsatz von Drittanbietern und Auftragsverarbeitern',
            'lead' => 'WebGuard setzt externe Dienstleister ausschließlich ein, soweit dies für den Betrieb erforderlich ist.',
            'items' => [
                'Hosting- und Infrastrukturanbieter (Compute, Storage, Netzwerk, Backups).',
                'E-Mail-Versanddienstleister für transaktionale Nachrichten (z. B. Verifikations- und Benachrichtigungs-E-Mails).',
                'GitHub als OAuth-Anbieter, wenn Sie die Anmeldung über GitHub aktiv nutzen.',
                'Betriebs- und Sicherheitswerkzeuge, soweit zur Fehleranalyse und Stabilität erforderlich.',
            ],
            'note' => 'Soweit erforderlich werden Auftragsverarbeitungsverträge nach Art. 28 DSGVO abgeschlossen. Falls Anbieter Daten außerhalb der EU/des EWR verarbeiten, erfolgt dies nur auf Grundlage der Voraussetzungen nach Art. 44 ff. DSGVO.',
        ],
        'cookies' => [
            'title' => '5. Cookies und Wahlmöglichkeiten',
            'lead' => 'WebGuard verwendet technisch notwendige Cookies für Login- und Sitzungsfunktionen.',
            'items' => [
                'Session-Cookies für Authentifizierung und sichere Kontonutzung.',
                'Sicherheits-/CSRF-Cookies, die für geschützte Formulare und Sitzungen notwendig sind.',
                'Präferenz-Cookie für die Sprachwahl (`webguard_locale`).',
                'Derzeit werden keine Marketing- oder Tracking-Cookies eingesetzt.',
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
            'lead' => 'Personenbezogene Daten werden nur so lange gespeichert, wie dies für vertragliche, gesetzliche und betriebliche Zwecke erforderlich ist. In der aktuellen App-Konfiguration werden z. B. gelesene Benachrichtigungen regelmäßig nach rund einem Monat gelöscht und Gast-Benachrichtigungen nach rund einer Woche entfernt. Ältere Roh-Monitoringdaten werden regelmäßig in eine Archivtabelle überführt. Bei Löschung von Konten oder Monitorings werden zugehörige Daten im Rahmen der technischen Löschprozesse entfernt.',
        ],
        'security' => [
            'title' => '8. Sicherheitsmaßnahmen',
            'lead' => 'WebGuard setzt angemessene technische und organisatorische Maßnahmen ein, um personenbezogene Daten vor unbefugtem Zugriff, Verlust oder Manipulation zu schützen. Dazu gehören u. a. rollenbasierte Zugriffssteuerung, tokenbasierte API-Authentifizierung sowie die gehashte Speicherung von Passwörtern und Instanz-API-Schlüsseln.',
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
