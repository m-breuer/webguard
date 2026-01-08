<?php

declare(strict_types=1);

return [
    'ssl_expiry_warning' => [
        'subject' => 'SSL-Zertifikat-Ablaufwarnung für :monitoringName',
        'greeting' => 'Hallo!',
        'intro' => 'Ihr SSL-Zertifikat für **:monitoringName** (:monitoringTarget) läuft bald ab!',
        'expiry_date' => 'Es läuft am **:expiryDate** ab.',
        'action_text' => 'Bitte erneuern Sie Ihr Zertifikat, um Dienstunterbrechungen zu vermeiden.',
        'button_text' => 'Überwachungsdetails anzeigen',
        'salutation' => 'Danke,',
    ],
    'status_change_notification' => [
        'subject' => 'Überwachungsstatusänderung: :monitoringName',
        'greeting' => 'Hallo :userName,',
        'intro' => 'Dies informiert Sie darüber, dass sich der Status Ihrer Überwachung ":monitoringName" geändert hat.',
        'new_status' => 'Neuer Status: :message',
        'button_text' => 'Überwachungsseite anzeigen',
        'salutation' => 'Vielen Dank,',
    ],
    'unread_notifications_reminder' => [
        'subject' => 'Aktion auf der Plattform erforderlich',
        'greeting' => 'Hallo :userName,',
        'intro' => 'Sie haben :count ungelesene Benachrichtigungen auf der WebGuard-Plattform.',
        'action_text' => 'Bitte besuchen Sie Ihre Benachrichtigungsseite, um diese zu überprüfen.',
        'button_text' => 'Benachrichtigungen anzeigen',
        'salutation' => 'Vielen Dank,',
    ],
    'general' => [
        'team_name' => 'Das WebGuard-Team',
    ],
];
