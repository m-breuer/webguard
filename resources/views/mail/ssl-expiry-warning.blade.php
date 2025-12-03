@extends('layouts.mail')

@section('title', __('mail.ssl_expiry_warning.subject', ['monitoringName' => $monitoringName]))

@section('content')
    <h1>{{ __('mail.ssl_expiry_warning.greeting') }}</h1>

    <p>{{ __('mail.ssl_expiry_warning.intro', ['monitoringName' => $monitoringName, 'monitoringTarget' => $monitoringTarget]) }}</p>

    <p>{{ __('mail.ssl_expiry_warning.expiry_date', ['expiryDate' => $expiryDate]) }}</p>

    <p>{{ __('mail.ssl_expiry_warning.action_text') }}</p>

    <a href="{{ $monitoringUrl }}" class="button">{{ __('mail.ssl_expiry_warning.button_text') }}</a>

    <p>{{ __('mail.ssl_expiry_warning.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
