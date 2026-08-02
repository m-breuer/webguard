<?php

declare(strict_types=1);

return [
    'title' => 'Teams',
    'create' => [
        'title' => 'Team erstellen',
    ],
    'edit' => [
        'title' => ':team bearbeiten',
    ],
    'fields' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
        'email' => 'E-Mail',
        'role' => 'Rolle',
        'members' => 'Mitglieder',
        'monitorings' => 'Überwachungen',
        'created_at' => 'Erstellt',
    ],
    'roles' => [
        'admin' => 'Admin',
        'member' => 'Mitglied',
    ],
    'messages' => [
        'created' => 'Team wurde erstellt.',
        'updated' => 'Team wurde aktualisiert.',
        'deleted' => 'Team wurde gelöscht.',
        'member_updated' => 'Teammitglied wurde aktualisiert.',
        'member_removed' => 'Teammitglied wurde entfernt.',
        'left' => 'Sie haben das Team verlassen.',
        'invitation_sent' => 'Einladung wurde versendet.',
        'invitation_revoked' => 'Einladung wurde zurückgezogen.',
        'invitation_accepted' => 'Einladung wurde angenommen.',
        'login_to_accept' => 'Melden Sie sich mit der eingeladenen E-Mail-Adresse an oder registrieren Sie sich, um die Einladung anzunehmen.',
    ],
    'validation' => [
        'last_admin' => 'Ein Team muss immer mindestens einen Admin haben.',
        'already_member' => 'Diese E-Mail-Adresse gehört bereits zu einem Teammitglied.',
        'email_mismatch' => 'Diese Einladung wurde für eine andere E-Mail-Adresse ausgestellt.',
        'not_admin' => 'Für diese Aktion müssen Sie Team-Admin sein.',
        'not_member' => 'Sie sind kein Mitglied dieses Teams.',
        'delete_last_admin' => 'Dieser Nutzer ist der letzte Admin von :team.',
    ],
    'actions' => [
        'create' => 'Team erstellen',
        'edit' => 'Team bearbeiten',
        'delete' => 'Team löschen',
        'invite' => 'Mitglied einladen',
        'revoke' => 'Zurückziehen',
        'remove' => 'Entfernen',
        'leave' => 'Team verlassen',
        'save_role' => 'Rolle speichern',
    ],
    'sections' => [
        'members' => 'Mitglieder',
        'invitations' => 'Offene Einladungen',
        'invite' => 'Mitglied einladen',
        'details' => 'Teamdetails',
        'notification_preferences' => 'Ihre Benachrichtigungseinstellungen',
    ],
    'empty' => [
        'teams' => 'Noch keine Teams vorhanden.',
        'invitations' => 'Keine offenen Einladungen.',
    ],
    'ownership' => [
        'private' => 'Privat',
        'team' => 'Team',
        'select_label' => 'Besitz',
        'private_help' => 'Private Überwachungen sind nur für Sie sichtbar.',
        'team_help' => 'Team-Überwachungen sind für alle Teammitglieder sichtbar. Nur Team-Admins können sie bearbeiten.',
        'move_to_team' => 'In Team verschieben',
        'move_to_private' => 'Privat übernehmen',
        'moved_to_team' => 'Überwachung wurde ins Team verschoben.',
        'moved_to_private' => 'Überwachung wurde in privaten Besitz verschoben.',
        'no_admin_teams' => 'Sie sind noch in keinem Team Admin.',
    ],
    'mail' => [
        'invitation' => [
            'subject' => 'Einladung zu :team',
            'heading' => 'Sie wurden zu :team eingeladen',
            'intro' => 'Nehmen Sie die Einladung an, um Team-Überwachungen für :team zu sehen.',
            'action' => 'Einladung annehmen',
            'expires' => 'Diese Einladung läuft am :date um :time Uhr ab.',
            'outro' => 'Falls Sie diese Einladung nicht erwartet haben, können Sie diese E-Mail ignorieren.',
        ],
    ],
];
