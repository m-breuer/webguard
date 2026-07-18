@php
    $healthPercentage = $summary['total'] > 0
        ? (int) round(($summary['healthy'] / $summary['total']) * 100)
        : 0;
    $healthCircumference = 2 * pi() * 42;
    $healthOffset = $healthCircumference - ($healthPercentage / 100) * $healthCircumference;

    $stateTone = match ($overallState) {
        'degraded' => [
            'ring' => 'text-red-500',
            'soft' => 'bg-red-50 dark:bg-red-950/20',
            'border' => 'border-red-200 dark:border-red-900/60',
        ],
        'attention' => [
            'ring' => 'text-amber-500',
            'soft' => 'bg-amber-50 dark:bg-amber-950/20',
            'border' => 'border-amber-200 dark:border-amber-900/60',
        ],
        default => [
            'ring' => 'text-emerald-500',
            'soft' => 'bg-emerald-50 dark:bg-emerald-950/20',
            'border' => 'border-emerald-200 dark:border-emerald-900/60',
        ],
    };

    $recommendedHref = match ($recommendedAction) {
        'create' => route('monitorings.create'),
        'incidents' => route('incidents.analytics'),
        'unknown' => route('monitorings.index', ['lifecycle' => 'active']),
        'notifications' => route('notifications.index'),
        'maintenance' => route('maintenance.index', ['maintenance_status' => 'upcoming']),
        default => route('monitorings.index'),
    };

    $quickActions = [
        ['key' => 'create', 'href' => route('monitorings.create'), 'visible' => $canCreateMonitoring],
        ['key' => 'maintenance', 'href' => route('maintenance.index'), 'visible' => $canManageMaintenance],
        ['key' => 'incidents', 'href' => route('incidents.analytics'), 'visible' => true],
        ['key' => 'notifications', 'href' => route('notifications.index'), 'visible' => true],
        ['key' => 'status_pages', 'href' => route('status-pages.index'), 'visible' => true],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-heading type="h1">{{ __('dashboard.greeting', ['name' => Auth::user()->name]) }}</x-heading>
                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.description') }}</p>
            </div>

            <x-dashboard.action-link href="{{ route('monitorings.index') }}" class="shrink-0">
                {{ __('dashboard.open_monitorings') }}
            </x-dashboard.action-link>
        </div>
    </x-slot>

    <x-main>
        @if ($summary['total'] === 0)
            <x-dashboard.empty-state :can-create-monitoring="$canCreateMonitoring" modal />
        @else
            <div class="space-y-6">
                <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.75fr)]">
                    <x-dashboard.health-summary
                        :summary="$summary"
                        :health-percentage="$healthPercentage"
                        :health-circumference="$healthCircumference"
                        :health-offset="$healthOffset"
                        :state-tone="$stateTone"
                        :overall-state="$overallState"
                    />
                    <x-dashboard.next-action
                        :recommended-action="$recommendedAction"
                        :recommended-href="$recommendedHref"
                        :quick-actions="$quickActions"
                        :state-tone="$stateTone"
                    />
                </section>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <x-dashboard.panel
                        :heading="__('dashboard.attention.heading')"
                        :description="__('dashboard.attention.description')"
                        :href="route('incidents.analytics')"
                    >
                        @if ($attentionItems->isEmpty())
                            <p class="m-5 rounded-2xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.attention.empty') }}</p>
                        @else
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($attentionItems as $item)
                                    @php
                                        $itemStatusPage = $item['statusPage'] ?? null;
                                        $itemIncident = $item['monitoring']?->latestIncident;
                                        $itemHref = $item['type'] === 'delivery'
                                            ? route('notifications.index')
                                            : ($itemStatusPage && $itemIncident
                                                ? route('status-pages.show', [
                                                    'statusPage' => $itemStatusPage,
                                                    'incident_id' => $itemIncident->id,
                                                ]) . '#incident-workbench-' . $itemIncident->id
                                                : route('monitorings.show', $item['monitoring']));
                                        $itemText = match ($item['type']) {
                                            'incident' => __('dashboard.attention.incident', ['name' => $item['monitoring']->name]),
                                            'down' => __('dashboard.attention.down', ['name' => $item['monitoring']->name]),
                                            'unknown' => __('dashboard.attention.unknown', ['name' => $item['monitoring']->name]),
                                            'stale' => __('dashboard.attention.stale', ['name' => $item['monitoring']->name]),
                                            default => __('dashboard.attention.delivery', ['count' => $item['count']]),
                                        };
                                        $itemTone = match ($item['type']) {
                                            'incident', 'down', 'delivery' => ['icon' => 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-300', 'dot' => 'bg-red-500'],
                                            'stale' => ['icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-300', 'dot' => 'bg-amber-500'],
                                            default => ['icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300', 'dot' => 'bg-blue-500'],
                                        };
                                        $itemAction = $itemStatusPage && $itemIncident
                                            ? __('dashboard.attention.open_incident')
                                            : __('dashboard.attention.open');
                                    @endphp

                                    <x-dashboard.list-link :href="$itemHref" :icon-class="$itemTone['icon']" :action-label="$itemAction">
                                        <x-slot:icon>
                                            <span class="h-2.5 w-2.5 rounded-full {{ $itemTone['dot'] }}"></span>
                                        </x-slot:icon>
                                        <x-slot:title>{{ $itemText }}</x-slot:title>
                                        <x-slot:context>
                                            @if ($itemStatusPage && $itemIncident)
                                                <span class="text-purple-700 dark:text-purple-300">{{ __('dashboard.attention.status_page', ['name' => $itemStatusPage->name]) }}</span>
                                            @endif
                                            @if ($item['monitoring'])
                                                <span class="{{ $itemStatusPage && $itemIncident ? 'mt-1 block' : '' }}">{{ $item['monitoring']->target }}</span>
                                            @endif
                                        </x-slot:context>
                                    </x-dashboard.list-link>
                                @endforeach
                            </div>
                        @endif
                    </x-dashboard.panel>

                    <x-dashboard.panel
                        :heading="__('dashboard.maintenance.heading')"
                        :description="__('dashboard.maintenance.description')"
                        :href="route('maintenance.index')"
                    >
                        @if ($maintenanceMonitorings->isEmpty())
                            <p class="m-5 rounded-2xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.maintenance.empty') }}</p>
                        @else
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($maintenanceMonitorings as $monitoring)
                                    @php $isActiveMaintenance = $monitoring->isUnderMaintenance(); @endphp
                                    <x-dashboard.list-link
                                        :href="route('monitorings.show', $monitoring)"
                                        icon-class="bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-300"
                                        :status-class="$isActiveMaintenance ? 'text-amber-600 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400'"
                                    >
                                        <x-slot:icon>
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="3.5" y="5.5" width="17" height="15" rx="2" />
                                                <path stroke-linecap="round" d="M7.5 3.5v4m9-4v4M3.5 10h17" />
                                            </svg>
                                        </x-slot:icon>
                                        <x-slot:title>{{ $monitoring->name }}</x-slot:title>
                                        <x-slot:context>{{ $monitoring->maintenance_from->locale(app()->getLocale())->isoFormat('L LT') }}</x-slot:context>
                                        <x-slot:status>{{ $isActiveMaintenance ? __('dashboard.maintenance.active') : __('dashboard.maintenance.upcoming') }}</x-slot:status>
                                    </x-dashboard.list-link>
                                @endforeach
                            </div>
                        @endif
                    </x-dashboard.panel>
                </div>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    <x-dashboard.panel
                        :heading="__('dashboard.incidents.heading')"
                        :description="__('dashboard.incidents.description')"
                        :href="route('incidents.analytics')"
                    >
                        @if ($recentIncidents->isEmpty())
                            <p class="m-5 rounded-2xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.incidents.empty') }}</p>
                        @else
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($recentIncidents as $incident)
                                    <x-dashboard.list-link
                                        :href="route('monitorings.show', $incident->monitoring)"
                                        :id="'incident-' . $incident->id"
                                        :icon-class="$incident->up_at ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-300'"
                                        :status-class="$incident->up_at ? 'text-emerald-600 dark:text-emerald-300' : 'text-red-600 dark:text-red-300'"
                                    >
                                        <x-slot:icon>
                                            <span class="h-2.5 w-2.5 rounded-full {{ $incident->up_at ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                        </x-slot:icon>
                                        <x-slot:title>{{ $incident->monitoring->name }}</x-slot:title>
                                        <x-slot:context>{{ $incident->down_at->locale(app()->getLocale())->isoFormat('L LT') }}</x-slot:context>
                                        <x-slot:status>
                                            {{ $incident->up_at ? __('dashboard.incidents.resolved') : __('dashboard.incidents.ongoing') }}
                                            <span class="sr-only">{{ __('dashboard.incidents.open') }}</span>
                                        </x-slot:status>
                                    </x-dashboard.list-link>
                                @endforeach
                            </div>
                        @endif
                    </x-dashboard.panel>

                    <x-dashboard.panel
                        :heading="__('dashboard.trend.heading')"
                        :description="__('dashboard.trend.description')"
                    >
                        <x-slot name="actions">
                            <span class="hidden rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300 sm:inline-flex">{{ __('dashboard.trend.period') }}</span>
                        </x-slot>
                        <x-dashboard.trend-chart :trend="$trend" />
                    </x-dashboard.panel>
                </div>
            </div>
        @endif
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            <x-form-modal name="monitoring-form-modal" title="{{ __('monitoring.title') }}"
                description="{{ __('monitoring.form.sections.basic') }}" max-width="6xl">
                <div class="p-6" x-ref="content">
                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
