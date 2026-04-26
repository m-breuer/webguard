@extends('layouts.mail')

@section('title', __('mail.weekly_monitoring_digest.subject', [
    'from' => $digest['period_start']->toDateString(),
    'to' => $digest['period_end']->toDateString(),
]))

@section('content')
    @php
        $formatPercentage = static fn (?float $value): string => $value === null
            ? __('mail.weekly_monitoring_digest.no_data')
            : number_format($value, 2) . '%';
        $formatMinutes = static fn (int $minutes): string => __('mail.weekly_monitoring_digest.minutes', ['count' => $minutes]);
    @endphp

    <p>{{ __('mail.weekly_monitoring_digest.greeting', ['userName' => $user->name]) }}</p>
    <p>{{ __('mail.weekly_monitoring_digest.intro', [
        'from' => $digest['period_start']->toDateString(),
        'to' => $digest['period_end']->toDateString(),
    ]) }}</p>

    <h2>{{ __('mail.weekly_monitoring_digest.overview_heading') }}</h2>
    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-bottom: 20px;">
        <tbody>
            <tr>
                <td><strong>{{ __('mail.weekly_monitoring_digest.uptime_label') }}</strong></td>
                <td>{{ $formatPercentage($digest['overview']['uptime_percentage']) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('mail.weekly_monitoring_digest.incidents_label') }}</strong></td>
                <td>{{ $digest['overview']['incidents_count'] }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('mail.weekly_monitoring_digest.longest_downtime_label') }}</strong></td>
                <td>{{ $formatMinutes($digest['overview']['longest_downtime_minutes']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('mail.weekly_monitoring_digest.monitorings_heading') }}</h2>
    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr>
                <th align="left">{{ __('mail.weekly_monitoring_digest.monitor_label') }}</th>
                <th align="left">{{ __('mail.weekly_monitoring_digest.uptime_label') }}</th>
                <th align="left">{{ __('mail.weekly_monitoring_digest.incidents_label') }}</th>
                <th align="left">{{ __('mail.weekly_monitoring_digest.longest_downtime_label') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($digest['monitorings'] as $monitoring)
                <tr>
                    <td>
                        <strong>{{ $monitoring['name'] }}</strong><br>
                        <span style="color: #666;">{{ $monitoring['target'] }}</span>
                    </td>
                    <td>{{ $formatPercentage($monitoring['uptime_percentage']) }}</td>
                    <td>{{ $monitoring['incidents_count'] }}</td>
                    <td>{{ $formatMinutes($monitoring['longest_downtime_minutes']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('mail.weekly_monitoring_digest.warnings_heading') }}</h2>
    @if (count($digest['ssl_warnings']) < 1 && count($digest['domain_warnings']) < 1)
        <p>{{ __('mail.weekly_monitoring_digest.no_warnings') }}</p>
    @endif

    @if (count($digest['ssl_warnings']) > 0)
        <h3>{{ __('mail.weekly_monitoring_digest.ssl_warnings_heading') }}</h3>
        <ul>
            @foreach ($digest['ssl_warnings'] as $warning)
                <li>
                    <strong>{{ $warning['name'] }}</strong>
                    ({{ $warning['target'] }}) -
                    @if (! $warning['is_valid'])
                        {{ __('mail.weekly_monitoring_digest.invalid_warning') }}
                    @elseif ($warning['expires_at'])
                        {{ __('mail.weekly_monitoring_digest.expires_on', ['date' => $warning['expires_at']->toDateString()]) }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if (count($digest['domain_warnings']) > 0)
        <h3>{{ __('mail.weekly_monitoring_digest.domain_warnings_heading') }}</h3>
        <ul>
            @foreach ($digest['domain_warnings'] as $warning)
                <li>
                    <strong>{{ $warning['name'] }}</strong>
                    ({{ $warning['target'] }}) -
                    @if (! $warning['is_valid'])
                        {{ __('mail.weekly_monitoring_digest.invalid_warning') }}
                    @elseif ($warning['expires_at'])
                        {{ __('mail.weekly_monitoring_digest.expires_on', ['date' => $warning['expires_at']->toDateString()]) }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <p>
        <a href="{{ route('monitorings.index') }}" class="mail-button">{{ __('mail.weekly_monitoring_digest.button_text') }}</a>
    </p>

    <p>{{ __('mail.weekly_monitoring_digest.salutation') }}<br>
    {{ __('mail.general.team_name') }}</p>
@endsection
