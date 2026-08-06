<x-public-layout>
    <x-slot name="head">
        <title>{{ __('status_page.public.title', ['statusPage' => $statusPage->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <div class="mx-auto w-full max-w-4xl text-center">
            <x-heading class="text-3xl sm:text-4xl">{{ $statusPage->name }}</x-heading>
            @if ($statusPage->description)
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-300">{{ $statusPage->description }}</p>
            @endif
        </div>
    </x-slot>

    <x-main>
        @php
            $pageStatusSurfaceClasses = match ($pageStatusBadgeType) {
                'success' => 'border-emerald-200 bg-emerald-50/80 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100',
                'danger' => 'border-red-200 bg-red-50/80 text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-100',
                default => 'border-amber-200 bg-amber-50/80 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100',
            };
            $pageStatusDotClasses = match ($pageStatusBadgeType) {
                'success' => 'bg-emerald-500',
                'danger' => 'bg-red-500',
                default => 'bg-amber-500',
            };
            $pageStatusLabel = match ($pageStatus) {
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

            <section id="status-page-overall-status" class="rounded-2xl border px-5 py-5 shadow-sm sm:px-6 {{ $pageStatusSurfaceClasses }}">
                <div class="flex items-center gap-4">
                    <span class="h-3.5 w-3.5 shrink-0 rounded-full {{ $pageStatusDotClasses }}" aria-hidden="true"></span>
                    <div>
                        <p class="text-lg font-bold sm:text-xl">{{ $pageStatusLabel }}</p>
                        <p class="mt-1 text-sm opacity-80">
                            <span class="sr-only">{{ __('status_page.public.overall_status') }}: {{ mb_strtoupper($pageStatus) }}</span>
                            {{ __('status_page.public.overall_status_description') }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($components as $statusPageComponent)
                    <div id="status-page-component-{{ $statusPageComponent['model']->id }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <x-heading type="h2">{{ $statusPageComponent['model']->name }}</x-heading>
                                @if ($statusPageComponent['model']->description)
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $statusPageComponent['model']->description }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($statusPageComponent['hasMaintenance'])
                                    <x-badge type="info">{{ __('monitoring.index.table.maintenance') }}</x-badge>
                                @endif
                                <x-badge :type="$statusPageComponent['badgeType']">{{ mb_strtoupper($statusPageComponent['status']) }}</x-badge>
                            </div>
                        </div>

                        <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($statusPageComponent['monitorings'] as $monitoringItem)
                                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $monitoringItem['model']->name }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('monitoring.types.' . $monitoringItem['model']->type->value) }}
                                            @if ($monitoringItem['lastCheckedAt'])
                                                - {{ __('monitoring.detail.last_check') }}
                                                <x-date-time :value="$monitoringItem['lastCheckedAt']" />
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @if ($monitoringItem['isUnderMaintenance'])
                                            <x-badge type="info">{{ __('monitoring.index.table.maintenance') }}</x-badge>
                                        @endif
                                        <x-badge :type="$monitoringItem['badgeType']">{{ mb_strtoupper($monitoringItem['status']) }}</x-badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>

            @if ($hasAggregateCalendar)
                <section id="status-page-uptime-calendar">
                    <div class="mb-4">
                        <x-heading type="h2">{{ __('status_page.public.calendar.heading') }}</x-heading>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('status_page.public.calendar.description') }}
                        </p>
                    </div>

                    <div x-data="uptimeCalendar('{{ $statusPage->id }}', @js(route('public.status-pages.uptime-calendar', $statusPage)), 30)" x-init="fetchUptimeCalendar">
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
            @endif

            <section id="status-page-subscription">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <x-heading type="h2">{{ __('status_page.public.subscribe.heading') }}</x-heading>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('status_page.public.subscribe.description') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('public-status-pages.subscribers.store', $statusPage) }}"
                            class="w-full md:max-w-md">
                            @csrf

                            <x-input-label for="status-page-subscriber-email" :value="__('status_page.public.subscribe.email')" />
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                                <x-text-input id="status-page-subscriber-email" type="email" name="email"
                                    :value="old('email')" required autocomplete="email"
                                    :placeholder="__('status_page.public.subscribe.email_placeholder')" />
                                <x-primary-button class="shrink-0 justify-center">
                                    {{ __('status_page.public.subscribe.button') }}
                                </x-primary-button>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </form>
                    </div>
                </div>
            </section>

            <section id="status-page-incidents">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                    <x-heading type="h2">{{ __('status_page.public.recent_incidents') }}</x-heading>

                    @if ($incidents->isEmpty())
                        <div class="mt-5 rounded-xl border border-dashed border-gray-200 bg-slate-50 px-4 py-8 text-center dark:border-slate-700 dark:bg-slate-950/50">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300" aria-hidden="true">
                                <x-icon name="check" class="h-5 w-5" />
                            </span>
                            <p class="mt-3 font-semibold text-gray-900 dark:text-gray-100">{{ __('status_page.public.no_recent_incidents') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.detail.incidents.no_incidents') }}</p>
                        </div>
                    @else
                        <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($incidents as $incident)
                                <div class="py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $incident->monitoring->name }}
                                        </span>
                                        <x-badge :type="$incident->up_at ? 'success' : 'danger'">
                                            {{ $incident->up_at ? __('monitoring.public_label.resolved') : __('monitoring.public_label.ongoing') }}
                                        </x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('monitoring.detail.incidents.incident.down_at') }}:
                                        <x-date-time :value="$incident->down_at" />
                                        @if ($incident->up_at)
                                            - {{ __('monitoring.detail.incidents.incident.up_at') }}
                                            <x-date-time :value="$incident->up_at" />
                                        @endif
                                    </p>
                                    @if ($incident->updates->isNotEmpty())
                                        <div class="mt-4 space-y-3">
                                            @foreach ($incident->updates as $incidentUpdate)
                                                <div class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <x-badge :type="$incidentUpdate->status->badgeType()">
                                                            {{ __('status_page.incident_updates.statuses.' . $incidentUpdate->status->value) }}
                                                        </x-badge>
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                                            <x-date-time :value="$incidentUpdate->created_at" />
                                                        </span>
                                                    </div>
                                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                                        {{ $incidentUpdate->message }}
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </x-main>
</x-public-layout>
