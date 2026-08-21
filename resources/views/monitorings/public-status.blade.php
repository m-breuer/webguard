<x-public-layout>
    <x-slot name="head">
        <title>{{ __('monitoring.public_label.title', ['monitoringName' => $monitoring->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <div class="mx-auto w-full max-w-4xl text-center">
            <x-heading class="text-3xl sm:text-4xl">{{ $monitoring->name }}</x-heading>
            <div class="mx-auto mt-3 flex max-w-2xl flex-wrap items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-300">
                <x-badge type="info">{{ __('monitoring.types.' . $monitoring->type->value) }}</x-badge>
                @if ($displayTarget)
                    <a href="{{ $displayTarget }}" target="_blank" rel="noopener" title="{{ $monitoring->name }}" class="break-all hover:text-gray-700 dark:hover:text-white">
                        {{ $displayTarget }}
                    </a>
                @else
                    <span>{{ __('monitoring.public_label.private_target') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <x-main>
        @php
            $statusSurfaceClasses = match ($statusBadgeType) {
                'success' => 'border-emerald-200 bg-emerald-50/80 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100',
                'danger' => 'border-red-200 bg-red-50/80 text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-100',
                default => 'border-amber-200 bg-amber-50/80 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100',
            };
            $statusDotClasses = match ($statusBadgeType) {
                'success' => 'bg-emerald-500',
                'danger' => 'bg-red-500',
                default => 'bg-amber-500',
            };
            $statusLabel = match ($status) {
                'up' => __('monitoring.index.workspace.operational'),
                'down' => __('dashboard.state.attention.title'),
                default => __('dashboard.summary.unknown'),
            };
        @endphp

        <div class="mx-auto max-w-5xl space-y-6">
            @if (session('status_page_subscription_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                    {{ session('status_page_subscription_success') }}
                </div>
            @endif

            <section id="public-current-status" class="rounded-2xl border px-5 py-5 shadow-sm sm:px-6 {{ $statusSurfaceClasses }}">
                <div class="flex items-center gap-4">
                    <span class="h-3.5 w-3.5 shrink-0 rounded-full {{ $statusDotClasses }}" aria-hidden="true"></span>
                    <div>
                        <p class="text-lg font-bold sm:text-xl">{{ $statusLabel }}</p>
                        <p class="mt-1 text-sm opacity-80">{{ __('monitoring.public_label.current_status') }}: {{ mb_strtoupper($status) }}</p>
                    </div>
                </div>
            </section>

            <section>
                <div id="public-monitoring-component-{{ $monitoring->id }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <x-heading type="h2">{{ $monitoring->name }}</x-heading>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('monitoring.types.' . $monitoring->type->value) }}
                                @if (data_get($statusNow, 'checked_at'))
                                    - {{ __('monitoring.detail.last_check') }} <x-date-time :value="data_get($statusNow, 'checked_at')" />
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($isUnderMaintenance)
                                <x-badge type="info">{{ __('monitoring.index.table.maintenance') }}</x-badge>
                            @endif
                            <x-badge :type="$statusBadgeType">{{ mb_strtoupper($status) }}</x-badge>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400 md:grid-cols-2">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('monitoring.detail.ssl.heading') }}</p>
                            @if (($monitoring->type === \App\Enums\MonitoringType::HTTP || $monitoring->type === \App\Enums\MonitoringType::KEYWORD) && $monitoring->sslResult)
                                <p class="mt-1 {{ $monitoring->sslResult->is_valid ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $monitoring->sslResult->is_valid ? __('monitoring.detail.ssl.valid') : __('monitoring.detail.ssl.expired') }}
                                </p>
                                @if ($monitoring->sslResult->expires_at)
                                    <p class="mt-1">{{ __('monitoring.detail.ssl.expires_in') }}: <x-date-time :value="$monitoring->sslResult->expires_at" format="date" /></p>
                                @endif
                            @else
                                <p class="mt-1">{{ __('monitoring.public_label.no_data') }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('monitoring.public_label.current_status') }}</p>
                            @if ($statusSince)
                                <p class="mt-1">{{ __('monitoring.index.table.since') }} <x-date-time :value="$statusSince" /></p>
                            @endif
                            @if ($monitoring->latestResponseResult?->http_status_code)
                                <p class="mt-1">HTTP {{ $monitoring->latestResponseResult->http_status_code }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            @if ($maintenanceWindow)
                <section id="public-maintenance-window">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <x-heading type="h2">{{ __('monitoring.public_label.maintenance.heading') }}</x-heading>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $maintenanceWindow['active'] ? __('monitoring.public_label.maintenance.active_description') : __('monitoring.public_label.maintenance.upcoming_description') }}
                                </p>
                            </div>
                            <x-badge :type="$maintenanceWindow['active'] ? 'info' : 'warning'">
                                {{ $maintenanceWindow['active'] ? __('monitoring.public_label.maintenance.active') : __('monitoring.public_label.maintenance.upcoming') }}
                            </x-badge>
                        </div>
                        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('monitoring.public_label.maintenance.starts_at') }}</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100"><x-date-time :value="$maintenanceWindow['starts_at']" /></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('monitoring.public_label.maintenance.ends_at') }}</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                    @if ($maintenanceWindow['ends_at'])
                                        <x-date-time :value="$maintenanceWindow['ends_at']" />
                                    @else
                                        {{ __('monitoring.public_label.maintenance.open_ended') }}
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>
            @endif

            <section id="public-uptime-summary">
                <div class="mb-4"><x-heading type="h2">{{ __('monitoring.detail.uptime.heading') }}</x-heading></div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ([7, 30, 90] as $days)
                        @php
                            $summary = data_get($rangeSummaries, (string) $days);
                            $uptime = data_get($summary, 'uptime.percentage');
                            $incidentsCount = (int) data_get($summary, 'downtime.incidentsCount', 0);
                        @endphp
                        <div id="public-uptime-card-{{ $days }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ trans_choice('monitoring.public_label.range_days', $days, ['days' => $days]) }}</p>
                            <p class="mt-3 text-2xl font-bold text-purple-600 dark:text-purple-300">{{ is_numeric($uptime) ? number_format((float) $uptime, 2) . '%' : __('monitoring.public_label.no_data') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ trans_choice('monitoring.public_label.incidents_count', $incidentsCount, ['count' => $incidentsCount]) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="public-uptime-calendar-{{ $monitoring->id }}">
                <div class="mb-4"><x-heading type="h2">{{ __('monitoring.detail.calendar.heading') }}</x-heading></div>
                <div x-data="uptimeCalendar('{{ $monitoring->id }}', @js(route('public.monitorings.uptime-calendar', $monitoring)))" x-init="fetchUptimeCalendar">
                    <template x-if="isLoading">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p>{{ __('calendar.loading') }}</p></div>
                    </template>
                    <template x-if="! isLoading && calendarData"><div x-data="{ data: calendarData }">@include('components.monitoring-calendar')</div></template>
                </div>
            </section>

            <section id="public-status-subscription">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <x-heading type="h2">{{ __('monitoring.public_label.subscribe.heading') }}</x-heading>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.public_label.subscribe.description') }}</p>
                        </div>
                        <form method="POST" action="{{ route('public-status-pages.subscribers.store', $monitoring) }}" class="w-full md:max-w-md">
                            @csrf
                            <x-input-label for="status-page-subscriber-email" :value="__('monitoring.public_label.subscribe.email')" />
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                                <x-text-input id="status-page-subscriber-email" type="email" name="email" :value="old('email')" required autocomplete="email" :placeholder="__('monitoring.public_label.subscribe.email_placeholder')" />
                                <x-primary-button class="shrink-0 justify-center">{{ __('monitoring.public_label.subscribe.button') }}</x-primary-button>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </form>
                    </div>
                </div>
            </section>

            <section id="public-incidents">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900">
                    <x-heading type="h2">{{ __('monitoring.public_label.recent_incidents') }}</x-heading>
                    @if ($incidents->isEmpty())
                        <div class="mt-5 rounded-xl border border-dashed border-gray-200 bg-slate-50 px-4 py-8 text-center dark:border-slate-700 dark:bg-slate-950/50">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300" aria-hidden="true"><x-icon name="check" class="h-5 w-5" /></span>
                            <p class="mt-3 font-semibold text-gray-900 dark:text-gray-100">{{ __('status_page.public.no_recent_incidents') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.incidents.no_incidents') }}</p>
                        </div>
                    @else
                        <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($incidents as $incident)
                                @php
                                    $downAt = \Illuminate\Support\Facades\Date::parse($incident->downAt);
                                    $upAt = $incident->upAt ? \Illuminate\Support\Facades\Date::parse($incident->upAt) : null;
                                    $duration = $downAt->locale(app()->getLocale())->diffForHumans($upAt ?? now(), true);
                                @endphp
                                <div class="py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100"><x-date-time :value="$downAt" /></span>
                                        <x-badge :type="$upAt ? 'success' : 'danger'">{{ $upAt ? __('monitoring.public_label.resolved') : __('monitoring.public_label.ongoing') }}</x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('monitoring.detail.incidents.incident.duration') }}: {{ $duration }}
                                        @if ($upAt)
                                            - {{ __('monitoring.detail.incidents.incident.up_at') }} <x-date-time :value="$upAt" />
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </x-main>
</x-public-layout>
