<?php

declare(strict_types=1);

return [
    'title' => 'Statusseiten',
    'components_count' => ':count Komponente|:count Komponenten',
    'state' => [
        'public' => 'Öffentlich',
        'private' => 'Privat',
    ],
    'empty' => [
        'title' => 'Noch keine Statusseiten',
        'text' => 'Gruppieren Sie Überwachungen in kundenorientierte Komponenten wie API, Web App, Worker oder Datenbank.',
    ],
    'create' => [
        'title' => 'Statusseite erstellen',
    ],
    'edit' => [
        'title' => 'Statusseite bearbeiten: :statusPage',
    ],
    'form' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
        'is_public' => 'Diese Statusseite veröffentlichen',
        'components' => 'Komponenten',
        'component_name' => 'Komponentenname',
        'component_description' => 'Komponentenbeschreibung',
        'monitorings' => 'Überwachungen',
        'monitoring_group' => 'Monitoring-Gruppe',
        'monitoring_group_source_help' => 'Diese Komponente übernimmt ihre Überwachungen dynamisch aus der Monitoring-Gruppe.',
        'add_component' => 'Komponente hinzufügen',
        'remove_component' => 'Komponente entfernen',
    ],
    'actions' => [
        'public_page' => 'Öffentliche Seite',
        'delete_confirmation' => 'Sind Sie sicher, dass Sie diese Statusseite löschen möchten?',
    ],
    'messages' => [
        'created' => 'Statusseite erfolgreich erstellt.',
        'updated' => 'Statusseite erfolgreich aktualisiert.',
        'deleted' => 'Statusseite erfolgreich gelöscht.',
    ],
    'incident_updates' => [
        'heading' => 'Vorfall-Updates',
        'description' => 'Veröffentlichen Sie manuelle Updates, damit Besucher den Vorfallsverlauf verfolgen können.',
        'status' => 'Status',
        'message' => 'Update-Nachricht',
        'add' => 'Update hinzufügen',
        'statuses' => [
            'investigating' => 'Wird untersucht',
            'identified' => 'Identifiziert',
            'monitoring' => 'Unter Beobachtung',
            'resolved' => 'Behoben',
        ],
        'messages' => [
            'created' => 'Vorfall-Update erfolgreich hinzugefügt.',
        ],
    ],
    'incident_review' => [
        'heading' => 'Interne Vorfallauswertung',
        'description' => 'Dokumentieren Sie, was den Vorfall verursacht hat und was ihn behoben hat. Diese Notizen bleiben privat und erscheinen nicht auf der öffentlichen Statusseite.',
        'problem' => 'Was war das Problem?',
        'problem_placeholder' => 'Beschreiben Sie die Ursache, beitragende Faktoren oder Auswirkungen für Kunden.',
        'resolution' => 'Was hat es behoben?',
        'resolution_placeholder' => 'Beschreiben Sie die Maßnahme, den Fix oder die Wiederherstellungsschritte.',
        'save' => 'Auswertungsnotizen speichern',
        'messages' => [
            'updated' => 'Auswertungsnotizen zum Vorfall erfolgreich aktualisiert.',
        ],
    ],
    'public' => [
        'title' => ':statusPage - Status',
        'overall_status' => 'Gesamtstatus',
        'recent_incidents' => 'Letzte Vorfälle',
        'subscribe' => [
            'button' => 'Abonnieren',
            'confirmation_sent' => 'Falls erforderlich, haben wir eine Bestätigungs-E-Mail zum Abschließen des Abonnements gesendet.',
            'confirmed' => 'Ihr Statusseiten-Update-Abonnement ist aktiv.',
            'description' => 'Erhalten Sie Vorfalls- und Wiederherstellungs-Updates für diese Statusseite per E-Mail.',
            'email' => 'E-Mail-Adresse',
            'email_placeholder' => 'sie@example.com',
            'heading' => 'Updates abonnieren',
            'unsubscribe_button' => 'Abbestellen',
            'unsubscribe_confirmation' => 'Sind Sie sicher, dass Sie diese Statusseiten-Updates abbestellen möchten?',
            'unsubscribe_description' => 'Keine Statusseiten-Updates mehr an :email senden.',
            'unsubscribe_heading' => 'Statusseiten-Updates abbestellen',
            'unsubscribe_title' => 'Updates für :statusPageName abbestellen',
            'unsubscribed' => 'Sie wurden von den Statusseiten-Updates abgemeldet.',
        ],
    ],
    'validation' => [
        'fix_errors' => 'Bitte korrigieren Sie die markierten Statusseiten-Einstellungen.',
        'monitoring_not_accessible' => 'Die ausgewählte Überwachung ist nicht zugänglich.',
    ],
];
