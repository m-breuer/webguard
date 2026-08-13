<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-heading type="h1">{{ __('dashboard.greeting', ['name' => Auth::user()->name]) }}</x-heading>
                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.description') }}</p>
            </div>
        </div>
    </x-slot>

    <x-main>
        @php
            $dashboardCopy = [
                'emptyTitle' => __('dashboard.empty.title'),
                'emptyDescription' => __('dashboard.empty.description'),
                'createMonitoring' => __('dashboard.quick_actions.create'),
                'serviceLandscape' => __('dashboard.signal_room.service_landscape'),
                'signalRoomHeading' => __('dashboard.signal_room.heading'),
                'activeServices' => __('dashboard.signal_room.active_services'),
                'searchPlaceholder' => __('dashboard.signal_room.search_placeholder'),
                'allFilter' => __('dashboard.signal_room.filters.all'),
                'attentionFilter' => __('dashboard.signal_room.filters.attention'),
                'maintenanceFilter' => __('dashboard.signal_room.filters.maintenance'),
                'pausedFilter' => __('dashboard.signal_room.filters.paused'),
                'signalTab' => __('dashboard.signal_room.tabs.signal'),
                'checksTab' => __('dashboard.signal_room.tabs.checks'),
                'incidentsTab' => __('dashboard.signal_room.tabs.incidents'),
                'historyTab' => __('dashboard.signal_room.tabs.history'),
                'fullDetails' => __('dashboard.signal_room.full_details'),
                'healthy' => __('dashboard.summary.healthy'),
                'down' => __('dashboard.summary.down'),
                'unknown' => __('dashboard.summary.unknown'),
                'paused' => __('dashboard.summary.paused'),
                'maintenance' => __('dashboard.summary.maintenance'),
                'attention' => __('dashboard.attention.heading'),
                'incidents' => __('dashboard.incidents.open'),
                'notifications' => __('dashboard.quick_actions.notifications'),
                'noAttention' => __('dashboard.attention.empty'),
                'noMaintenance' => __('dashboard.maintenance.empty'),
                'recentIncidents' => __('dashboard.incidents.heading'),
                'noIncidents' => __('dashboard.incidents.empty'),
                'trend' => __('dashboard.trend.heading'),
                'noTrendData' => __('dashboard.trend.no_data'),
                'previous' => __('pagination.previous'),
                'next' => __('pagination.next'),
                'statusLabels' => [
                    'up' => __('dashboard.signal_room.statuses.up'),
                    'down' => __('dashboard.signal_room.statuses.down'),
                    'unknown' => __('dashboard.signal_room.statuses.unknown'),
                    'paused' => __('dashboard.signal_room.statuses.paused'),
                    'maintenance' => __('dashboard.signal_room.statuses.maintenance'),
                    'healthy' => __('dashboard.state.healthy.title'),
                    'degraded' => __('dashboard.state.degraded.title'),
                    'attention' => __('dashboard.state.attention.title'),
                    'new' => __('dashboard.empty.title'),
                ],
            ];
        @endphp

        <div
            id="dashboard-page-content"
            data-dashboard-loader
            data-api-endpoint="{{ route('api.v1.internal.ui.dashboard', request()->only('service_page'), absolute: false) }}"
            data-error-message="{{ __('search.messages.error') }}"
            data-copy='@json($dashboardCopy)'
            x-data="dashboardLoader()"
            class="space-y-6"
            aria-live="polite"
        >
            <x-container>
                <div data-dashboard-loading>
                    <x-loading-skeleton variant="dashboard" />
                </div>
                <p data-dashboard-error class="text-sm font-semibold text-red-600 dark:text-red-300" hidden>
                    {{ __('search.messages.error') }}
                </p>
            </x-container>
        </div>

        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            <x-form-modal
                name="monitoring-form-modal"
                title="{{ __('monitoring.title') }}"
                description="{{ __('monitoring.form.sections.basic') }}"
                max-width="6xl"
            >
                <div class="p-6" x-ref="content">
                    <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
