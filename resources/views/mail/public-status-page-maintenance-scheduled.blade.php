@extends('layouts.mail')

@section('title', __('mail.public_status_page_maintenance_scheduled.title'))
@section('eyebrow', __('mail.general.notification_eyebrow'))

@section('content')
    <p>{{ __('mail.public_status_page_maintenance_scheduled.greeting') }}</p>

    <p>{{ __('mail.public_status_page_maintenance_scheduled.' . ($recurring ? 'recurring_intro' : 'intro'), ['statusPageName' => $statusPage->name]) }}</p>

    <p>{{ __('mail.public_status_page_maintenance_scheduled.affected_services') }}</p>
    <ul>
        @foreach ($monitorings as $monitoring)
            <li>{{ $monitoring->name }}</li>
        @endforeach
    </ul>

    <p>{{ __('mail.public_status_page_maintenance_scheduled.starts_at', ['date' => $startsAt, 'timezone' => $timezone]) }}</p>
    @if ($endsAt !== null)
        <p>{{ __('mail.public_status_page_maintenance_scheduled.ends_at', ['date' => $endsAt, 'timezone' => $timezone]) }}</p>
    @else
        <p>{{ __('mail.public_status_page_maintenance_scheduled.open_ended') }}</p>
    @endif

    <p>
        <a href="{{ $statusPageUrl }}" class="mail-button">{{ __('mail.public_status_page_maintenance_scheduled.button_text') }}</a>
    </p>

    <p>
        <a href="{{ $unsubscribeUrl }}">{{ __('mail.public_status_page_maintenance_scheduled.unsubscribe_text') }}</a>
    </p>

    <p>{{ __('mail.public_status_page_maintenance_scheduled.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
