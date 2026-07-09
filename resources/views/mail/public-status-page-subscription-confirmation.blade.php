@extends('layouts.mail')

@section('title', __('mail.public_status_page_subscription_confirmation.title'))
@section('eyebrow', __('mail.general.notification_eyebrow'))

@section('content')
    <p>{{ __('mail.public_status_page_subscription_confirmation.greeting') }}</p>

    <p>{{ __('mail.public_status_page_subscription_confirmation.intro', ['statusPageName' => $statusPage->name]) }}</p>

    <p>{{ __('mail.public_status_page_subscription_confirmation.action_text') }}</p>

    <p>
        <a href="{{ $confirmUrl }}" class="mail-button">{{ __('mail.public_status_page_subscription_confirmation.button_text') }}</a>
    </p>

    <p>{{ __('mail.public_status_page_subscription_confirmation.ignore_text') }}</p>

    <p>{{ __('mail.public_status_page_subscription_confirmation.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
