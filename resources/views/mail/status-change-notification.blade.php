@extends('layouts.mail')

@section('title', __('mail.status_change_notification.subject', ['monitoringName' => $notification->monitoring->name]))

@section('content')
    <p>{{ __('mail.status_change_notification.greeting', ['userName' => $notification->monitoring->user->name]) }}</p>

    <p>{{ __('mail.status_change_notification.intro', ['monitoringName' => $notification->monitoring->name]) }}</p>

    <p>{{ __('mail.status_change_notification.new_status', ['message' => $notification->message]) }}</p>

    <a href="{{ route('monitorings.show', $notification->monitoring->id) }}" class="button">{{ __('mail.status_change_notification.button_text') }}</a>

    <p>{{ __('mail.status_change_notification.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection