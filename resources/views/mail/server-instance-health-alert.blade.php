@extends('layouts.mail')

@section('title', __('mail.server_instance_health_alert.title', ['status' => $healthStatusLabel]))
@section('eyebrow', __('mail.general.notification_eyebrow'))

@section('content')
    <p>{{ __('mail.server_instance_health_alert.greeting', ['userName' => $admin->name]) }}</p>

    @if ($isRecovery)
        <p>{{ __('mail.server_instance_health_alert.recovery_intro', ['instanceCode' => $serverInstance->code]) }}</p>
    @else
        <p>{{ __('mail.server_instance_health_alert.alert_intro', ['instanceCode' => $serverInstance->code, 'status' => $healthStatusLabel]) }}</p>
    @endif

    @if ($healthStatus === 'never_seen')
        <p>
            {{ __('mail.server_instance_health_alert.never_seen_details', [
                'ipAddress' => $serverInstance->ip_address ?: __('admin.server_instances.fields.none'),
                'neverSeenAlertAfterMinutes' => $neverSeenAlertAfterMinutes,
            ]) }}
        </p>
    @elseif ($isRecovery)
        <p>
            {{ __('mail.server_instance_health_alert.recovery_details', [
                'ipAddress' => $serverInstance->ip_address ?: __('admin.server_instances.fields.none'),
                'lastSeenAt' => $serverInstance->last_seen_at?->locale(app()->getLocale())->isoFormat('L LT') ?: __('admin.server_instances.health.never_seen'),
            ]) }}
        </p>
    @else
        <p>
            {{ __('mail.server_instance_health_alert.details', [
                'ipAddress' => $serverInstance->ip_address ?: __('admin.server_instances.fields.none'),
                'lastSeenAt' => $serverInstance->last_seen_at?->locale(app()->getLocale())->isoFormat('L LT') ?: __('admin.server_instances.health.never_seen'),
                'staleAfterMinutes' => $staleAfterMinutes,
            ]) }}
        </p>
    @endif

    <p>{{ __('mail.server_instance_health_alert.action_text') }}</p>

    <p>
        <a href="{{ route('admin.server-instances.index') }}" class="mail-button">{{ __('mail.server_instance_health_alert.button_text') }}</a>
    </p>

    <p>{{ __('mail.server_instance_health_alert.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
