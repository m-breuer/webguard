<?php

declare(strict_types=1);

return [
    'failed' => 'Diese Anmeldeinformationen stimmen nicht mit unseren Aufzeichnungen überein.',
    'password' => 'Das angegebene Passwort ist falsch.',
    'throttle' => 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in :seconds Sekunden erneut.',
    'register' => [
        'title' => 'Registrieren',
        'description' => 'Erstellen Sie ein neues Konto, um Ihre Dienste zu überwachen.',
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'button' => 'Registrieren',
        'login_button' => 'Zum Login',
        'already_registered' => 'Bereits registriert?',
        'terms_agreement' => 'Ich akzeptiere die <a href=":terms_link" target="_blank" rel="noopener" class="underline hover:text-gray-900 dark:hover:text-gray-100">Nutzungsbedingungen</a>.',
        'privacy_agreement' => 'Ich habe die <a href=":privacy_link" target="_blank" rel="noopener" class="underline hover:text-gray-900 dark:hover:text-gray-100">Datenschutzerklärung</a> gelesen und akzeptiere sie.',
    ],
    'login' => [
        'title' => 'Anmelden',
        'description' => 'Greifen Sie auf Ihr Konto zu, um Ihre Überwachungen zu verwalten.',
        'demo_hint' => 'Demo-Zugangsdaten sind vorausgefüllt. Sie können sich direkt anmelden.',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'remember' => 'Angemeldet bleiben',
        'forgot_password' => 'Passwort vergessen?',
        'button' => 'Anmelden',
        'register_button' => 'Registrieren',
        'demo_button' => 'Demo-Zugangsdaten nutzen',
    ],
    'forgot_password' => [
        'title' => 'Passwort vergessen?',
        'description' => 'Kein Problem. Teilen Sie uns einfach Ihre E-Mail-Adresse mit und wir senden Ihnen einen Link zum Zurücksetzen des Passworts.',
        'button' => 'Link zum Zurücksetzen des Passworts senden',
    ],
    'confirm_password' => [
        'title' => 'Passwort bestätigen',
        'description' => 'Dies ist ein sicherer Bereich der Anwendung. Bitte bestätigen Sie Ihr Passwort, bevor Sie fortfahren.',
    ],
    'reset_password' => [
        'title' => 'Passwort zurücksetzen',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'button' => 'Passwort zurücksetzen',
    ],
    'verify_email' => [
        'heading' => 'E-Mail-Adresse bestätigen',
        'subheading' => 'Posteingang überprüfen',
        'description' => 'Vielen Dank für Ihre Registrierung! Bevor Sie beginnen, bestätigen Sie bitte Ihre E-Mail-Adresse, indem Sie auf den Link klicken, den wir Ihnen gerade per E-Mail gesendet haben. Wenn Sie die E-Mail nicht erhalten haben, senden wir Ihnen gerne eine neue zu.',
        'link_sent' => 'Ein neuer Bestätigungslink wurde an die von Ihnen bei der Registrierung angegebene E-Mail-Adresse gesendet.',
        'resend_button' => 'Bestätigungs-E-Mail erneut senden',
    ],
    'logout' => 'Abmelden',
    'or_continue_with' => 'oder fahre fort mit',
    'github_login' => 'Mit GitHub anmelden',
    'auth_switch' => [
        'title' => 'Zugang wählen',
        'description' => 'Wählen Sie links den gewünschten Modus. Das Formular rechts passt sich direkt an.',
        'login' => 'Anmelden',
        'register' => 'Registrieren',
        'demo' => 'Demo-Zugang',
    ],
    'guest_login' => [
        'no_guest_user_found' => 'Kein Gastbenutzer gefunden.',
    ],
];
