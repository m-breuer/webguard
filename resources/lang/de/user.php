<?php

return [
    'title' => 'Benutzer',
    'text' => 'Alle Benutzerkonten in der Anwendung anzeigen und verwalten.',
    'actions' => [
        'create' => 'Benutzer erstellen',
        'edit' => 'Benutzer bearbeiten',
        'delete' => 'Benutzer löschen',
        'update' => 'Benutzer aktualisieren',
        'verify_email' => 'E-Mail bestätigen',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'role' => 'Rolle',
        'package' => 'Paket',
        'monitorings' => 'Überwachungen',
        'monitoring_limit' => 'Überwachungslimit',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],
    'messages' => [
        'user_created' => 'Benutzer erfolgreich erstellt.',
        'user_updated' => 'Benutzer erfolgreich aktualisiert.',
        'user_deleted' => 'Benutzer erfolgreich gelöscht.',
        'user_verified' => 'Benutzer erfolgreich bestätigt.',
        'cannot_edit_self' => 'Sie können sich nicht selbst bearbeiten.',
        'cannot_delete_self' => 'Sie können sich nicht selbst löschen.',
        'empty' => 'Keine Benutzer gefunden.',
        'email_verified' => 'E-Mail ist bestätigt.',
        'email_unverified' => 'E-Mail ist nicht bestätigt.',
    ],
    'delete' => [
        'title' => 'Benutzer löschen',
        'text' => 'Sobald ein Benutzer gelöscht wurde, werden alle zugehörigen Ressourcen und Daten dauerhaft gelöscht.',
        'confirmation_question' => 'Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?',
        'confirmation_warning' => 'Sobald ein Benutzer gelöscht wurde, werden alle zugehörigen Ressourcen und Daten dauerhaft gelöscht. Bitte bestätigen Sie Ihre Aktion.',
    ],
];
