@extends('layouts.mail')

@section('title', __('mail.unread_notifications_reminder.subject'))

@section('content')
    <p>{{ __('mail.unread_notifications_reminder.greeting', ['userName' => $user->name]) }}</p>

    <p>{{ __('mail.unread_notifications_reminder.intro', ['count' => $unreadNotificationsCount]) }}</p>

    <p>{{ __('mail.unread_notifications_reminder.action_text') }}</p>

    <a href="{{ route('notifications.index') }}" class="button">{{ __('mail.unread_notifications_reminder.button_text') }}</a>

    <p>{{ __('mail.unread_notifications_reminder.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection