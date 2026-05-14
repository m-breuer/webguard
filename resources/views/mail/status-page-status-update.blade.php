@extends('layouts.mail')

@section('title', __('mail.status_page_status_update.title', ['status' => $statusLabel]))
@section('eyebrow', __('mail.general.notification_eyebrow'))

@section('content')
    <p>{{ __('mail.status_page_status_update.greeting') }}</p>

    <p>{{ __('mail.status_page_status_update.intro', ['monitoringName' => $monitoring->name]) }}</p>

    <p>{{ __('mail.status_page_status_update.new_status', ['status' => $statusLabel]) }}</p>

    <p>
        <a href="{{ $statusPageUrl }}" class="mail-button">{{ __('mail.status_page_status_update.button_text') }}</a>
    </p>

    <p>
        <a href="{{ $unsubscribeUrl }}">{{ __('mail.status_page_status_update.unsubscribe_text') }}</a>
    </p>

    <p>{{ __('mail.status_page_status_update.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
