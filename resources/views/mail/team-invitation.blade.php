@extends('layouts.mail')

@section('title', __('team.mail.invitation.subject', ['team' => $invitation->team->name]))

@section('content')
    <h1>{{ __('team.mail.invitation.heading', ['team' => $invitation->team->name]) }}</h1>
    <p>{{ __('team.mail.invitation.intro', ['team' => $invitation->team->name]) }}</p>

    <p>
        <a href="{{ $acceptUrl }}" class="button">
            {{ __('team.mail.invitation.action') }}
        </a>
    </p>

    <p>{{ __('team.mail.invitation.expires', [
        'date' => $invitation->expires_at?->locale(app()->getLocale())->isoFormat('L'),
        'time' => $invitation->expires_at?->locale(app()->getLocale())->isoFormat('LT'),
    ]) }}</p>
    <p>{{ __('team.mail.invitation.outro') }}</p>
@endsection
