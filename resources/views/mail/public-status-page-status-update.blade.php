@extends('layouts.mail')

@section('title', __('mail.public_status_page_status_update.title', ['status' => $statusLabel]))
@section('eyebrow', __('mail.general.notification_eyebrow'))

@section('content')
    <p>{{ __('mail.public_status_page_status_update.greeting') }}</p>

    <p>{{ __('mail.public_status_page_status_update.intro', ['statusPageName' => $statusPage->name]) }}</p>

    <p>{{ __('mail.public_status_page_status_update.monitoring_status', [
        'monitoringName' => $monitoring->name,
        'status' => $statusLabel,
    ]) }}</p>

    <p>
        <a href="{{ $statusPageUrl }}" class="mail-button">{{ __('mail.public_status_page_status_update.button_text') }}</a>
    </p>

    <p>
        <a href="{{ $unsubscribeUrl }}">{{ __('mail.public_status_page_status_update.unsubscribe_text') }}</a>
    </p>

    <p>{{ __('mail.public_status_page_status_update.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
