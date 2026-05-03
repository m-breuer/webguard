<x-public-layout>
    <x-slot name="head">
        <meta name="robots" content="noindex">
        <title>{{ __('monitoring.public_label.title', ['monitoringName' => $monitoring->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <div>
            <x-heading>
                {{ $monitoring->name }}
            </x-heading>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-300">
                <x-badge type="info">{{ __('monitoring.types.' . $monitoring->type->value) }}</x-badge>
                @if ($displayTarget)
                    <a href="{{ $displayTarget }}" target="_blank" title="{{ $monitoring->name }}"
                        class="break-all hover:text-gray-700 dark:hover:text-white">
                        {{ $displayTarget }}
                    </a>
                @else
                    <span>{{ __('monitoring.public_label.private_target') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <x-main>
        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-container id="public-current-status">
                    <x-heading type="h2">{{ __('monitoring.public_label.current_status') }}</x-heading>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <x-badge :type="$statusBadgeType">
                            {{ mb_strtoupper($status) }}
                        </x-badge>
                        @if ($isUnderMaintenance)
                            <x-badge type="info">
                                {{ __('monitoring.index.table.maintenance') }}
                            </x-badge>
                        @endif
                    </div>
                    <div class="mt-4 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                        @if ($statusSince)
                            <p>
                                {{ __('monitoring.index.table.since') }}
                                {{ \Illuminate\Support\Facades\Date::parse($statusSince)->diffForHumans() }}
                            </p>
                        @endif
                        @if (data_get($statusNow, 'checked_at'))
                            <p>
                                {{ __('monitoring.detail.last_check') }}
                                {{ \Illuminate\Support\Facades\Date::parse(data_get($statusNow, 'checked_at'))->diffForHumans() }}
                            </p>
                        @endif
                        @if ($monitoring->latestResponseResult?->http_status_code)
                            <p>HTTP {{ $monitoring->latestResponseResult->http_status_code }}</p>
                        @endif
                    </div>
                </x-container>

                <x-container id="public-ssl-status">
                    <x-heading type="h2">{{ __('monitoring.detail.ssl.heading') }}</x-heading>
                    @if ($monitoring->type === \App\Enums\MonitoringType::HTTP || $monitoring->type === \App\Enums\MonitoringType::KEYWORD)
                        @if ($monitoring->sslResult)
                            <p
                                class="mt-3 font-semibold {{ $monitoring->sslResult->is_valid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $monitoring->sslResult->is_valid ? __('monitoring.detail.ssl.valid') : __('monitoring.detail.ssl.expired') }}
                            </p>
                            @if ($monitoring->sslResult->expires_at)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('monitoring.detail.ssl.expires_in') }}:
                                    {{ $monitoring->sslResult->expires_at->diffForHumans() }}
                                </p>
                            @endif
                        @else
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('monitoring.public_label.no_data') }}
                            </p>
                        @endif
                    @else
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('monitoring.public_label.no_data') }}
                        </p>
                    @endif
                </x-container>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach ([7, 30, 90] as $days)
                    @php
                        $summary = data_get($rangeSummaries, (string) $days);
                        $uptime = data_get($summary, 'uptime.percentage');
                        $incidentsCount = (int) data_get($summary, 'downtime.incidents_count', 0);
                    @endphp
                    <x-container id="public-uptime-card-{{ $days }}">
                        <x-heading type="h2">
                            {{ trans_choice('monitoring.public_label.range_days', $days, ['days' => $days]) }}
                        </x-heading>
                        <p class="mt-3 text-2xl font-bold text-purple-600 dark:text-purple-300">
                            {{ is_numeric($uptime) ? number_format((float) $uptime, 2) . '%' : __('monitoring.public_label.no_data') }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ trans_choice('monitoring.public_label.incidents_count', $incidentsCount, ['count' => $incidentsCount]) }}
                        </p>
                    </x-container>
                @endforeach
            </section>

            <section id="public-uptime-calendar-{{ $monitoring->id }}">
                <div class="mb-4">
                    <x-heading type="h2">{{ __('monitoring.detail.calendar.heading') }}</x-heading>
                </div>
                <div x-data="uptimeCalendar('{{ $monitoring->id }}')" x-init="fetchUptimeCalendar">
                    <template x-if="isLoading">
                        <x-container>
                            <p>{{ __('calendar.loading') }}</p>
                        </x-container>
                    </template>

                    <template x-if="!isLoading && calendarData">
                        <div x-data="{ data: calendarData }">
                            @include('components.monitoring-calendar')
                        </div>
                    </template>
                </div>
            </section>

            <section id="public-incidents">
                <x-container>
                    <x-heading type="h2">{{ __('monitoring.public_label.recent_incidents') }}</x-heading>

                    @if ($incidents->isEmpty())
                        <p class="mt-4 text-gray-500 dark:text-gray-400">
                            {{ __('monitoring.detail.incidents.no_incidents') }}
                        </p>
                    @else
                        <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($incidents as $incident)
                                @php
                                    $downAt = \Illuminate\Support\Facades\Date::parse($incident['down_at']);
                                    $upAt = $incident['up_at'] ? \Illuminate\Support\Facades\Date::parse($incident['up_at']) : null;
                                    $duration = $downAt->diffForHumans($upAt ?? now(), true);
                                @endphp
                                <div class="py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $downAt->toDayDateTimeString() }}
                                        </span>
                                        <x-badge :type="$upAt ? 'success' : 'danger'">
                                            {{ $upAt ? __('monitoring.public_label.resolved') : __('monitoring.public_label.ongoing') }}
                                        </x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('monitoring.detail.incidents.incident.duration') }}: {{ $duration }}
                                        @if ($upAt)
                                            - {{ __('monitoring.detail.incidents.incident.up_at') }} {{ $upAt->toDayDateTimeString() }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-container>
            </section>
        </div>
    </x-main>
</x-public-layout>
