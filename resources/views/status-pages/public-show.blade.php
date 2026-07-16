<x-public-layout>
    <x-slot name="head">
        <meta name="robots" content="noindex">
        <link rel="canonical" href="{{ route('public-status-pages.show', $statusPage) }}">
        <title>{{ __('status_page.public.title', ['statusPage' => $statusPage->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <div>
            <x-heading>{{ $statusPage->name }}</x-heading>
            @if ($statusPage->description)
                <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-300">{{ $statusPage->description }}</p>
            @endif
        </div>
        <x-badge :type="$pageStatusBadgeType">
            {{ __('status_page.public.overall_status') }}: {{ mb_strtoupper($pageStatus) }}
        </x-badge>
    </x-slot>

    <x-main>
        <div class="space-y-6">
            @if (session('status_page_subscription_success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                    {{ session('status_page_subscription_success') }}
                </div>
            @endif

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($components as $statusPageComponent)
                    <x-container id="status-page-component-{{ $statusPageComponent['model']->id }}">
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
                                                {{ $monitoringItem['lastCheckedAt']->diffForHumans() }}
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
                    </x-container>
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
                <x-container>
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
                </x-container>
            </section>

            <section id="status-page-incidents">
                <x-container>
                    <x-heading type="h2">{{ __('status_page.public.recent_incidents') }}</x-heading>

                    @if ($incidents->isEmpty())
                        <p class="mt-4 text-gray-500 dark:text-gray-400">
                            {{ __('monitoring.detail.incidents.no_incidents') }}
                        </p>
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
                                        {{ $incident->down_at->toDayDateTimeString() }}
                                        @if ($incident->up_at)
                                            - {{ __('monitoring.detail.incidents.incident.up_at') }}
                                            {{ $incident->up_at->toDayDateTimeString() }}
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
                                                            {{ $incidentUpdate->created_at->toDayDateTimeString() }}
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
                </x-container>
            </section>
        </div>
    </x-main>
</x-public-layout>
