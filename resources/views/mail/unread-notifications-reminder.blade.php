@extends('layouts.mail')

@section('title', __('mail.unread_notifications_reminder.subject'))
@section('eyebrow', __('mail.general.notification_eyebrow'))

@section('content')
    <p>{{ __('mail.unread_notifications_reminder.greeting', ['userName' => $user->name]) }}</p>

    <p>{{ __('mail.unread_notifications_reminder.intro', ['count' => $unreadNotificationsCount]) }}</p>

    <div class="mail-card">
        <p class="mail-card-label">{{ __('mail.unread_notifications_reminder.count_label') }}</p>
        <p class="mail-card-value">{{ $unreadNotificationsCount }}</p>
    </div>

    <p>{{ __('mail.unread_notifications_reminder.action_text') }}</p>

    <p>
        <a href="{{ route('notifications.index') }}" class="mail-button">{{ __('mail.unread_notifications_reminder.button_text') }}</a>
    </p>

    <p>{{ __('mail.unread_notifications_reminder.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
