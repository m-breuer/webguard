<?php

declare(strict_types=1);

return [
    'title' => 'API',
    'text' => 'Verwalten Sie Ihre API-Schlüssel und Zugriffstoken für sichere API-Interaktionen.',
    'configuration' => [
        'heading' => 'API-Konfiguration',
        'description' => 'Verwalten Sie Ihre API-Einstellungen und Konfigurationen in diesem Bereich.',
        'fields' => [
            'token' => 'Ihr API-Token',
        ],
        'actions' => [
            'generate_token' => 'Token generieren',
            'copy' => 'Kopieren',
            'revoke_token' => 'Token widerrufen',
        ],
        'messages' => [
            'copied' => 'API-Schlüssel in die Zwischenablage kopiert!',
            'tokens_deleted' => 'Token erfolgreich gelöscht.',
            'api_key_confidential_warning' => 'Halten Sie Ihren API-Schlüssel vertraulich. Wenn Sie glauben, dass Ihr Schlüssel kompromittiert wurde, können Sie einen neuen generieren.',
        ],
    ],
    'logs' => [
        'title' => 'API-Protokolle',
        'description' => 'Zeigen Sie Ihre API-Nutzungsprotokolle an, um Anfragen und Antworten zu überwachen.',
        'fields' => [
            'date' => 'Datum',
            'email' => 'E-Mail',
            'endpoint' => 'Endpunkt',
        ],
        'messages' => [
            'no_logs' => 'Keine API-Protokolle gefunden.',
        ],
    ],
    'docs' => [
        'heading' => 'API-Dokumentation',
        'description' => 'Alle verfügbaren Endpunkte, Authentifizierung und Nutzungsbeispiele.',
        'link' => 'Entdecken Sie die neueste Dokumentation hier.',
    ],
];
