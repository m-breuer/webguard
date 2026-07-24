<?php

declare(strict_types=1);

return [
    'meta_title' => ':status | :app',
    'eyebrow' => 'WEBGUARD / SYSTEMANTWORT',
    'status_label' => 'HTTP-Status',
    'return_hint' => 'Die Monitoring-Oberfläche ist weiterhin erreichbar. Sie können im zentralen Workspace fortfahren.',
    'actions' => [
        'dashboard' => 'Dashboard öffnen',
        'login' => 'Zum Login',
    ],
    'status' => [
        '400' => [
            'title' => 'Die Anfrage konnte nicht verarbeitet werden.',
            'message' => 'WebGuard hat eine ungültige Anfrage erhalten. Bitte prüfen Sie die Adresse und versuchen Sie es erneut.',
        ],
        '403' => [
            'title' => 'Dieser Bereich ist geschützt.',
            'message' => 'Sie haben keine Berechtigung, auf diese WebGuard-Ressource zuzugreifen.',
        ],
        '404' => [
            'title' => 'Dieses Signal wurde nicht gefunden.',
            'message' => 'Die Seite wurde möglicherweise verschoben, ist abgelaufen oder existiert unter dieser Adresse nicht.',
        ],
        '419' => [
            'title' => 'Ihre Sitzung ist abgelaufen.',
            'message' => 'Aus Sicherheitsgründen ist diese Anfrage nicht mehr gültig. Bitte starten Sie erneut.',
        ],
        '429' => [
            'title' => 'Zu viele Anfragen.',
            'message' => 'WebGuard schützt diesen Workspace. Bitte warten Sie einen Moment und versuchen Sie es erneut.',
        ],
        '500' => [
            'title' => 'Auf unserer Seite ist ein Fehler aufgetreten.',
            'message' => 'WebGuard konnte diese Anfrage nicht abschließen. Bitte versuchen Sie es gleich noch einmal.',
        ],
        '503' => [
            'title' => 'WebGuard ist vorübergehend nicht verfügbar.',
            'message' => 'Der Dienst wird gerade vorbereitet oder gewartet. Bitte versuchen Sie es in Kürze erneut.',
        ],
    ],
];
