@php
    $stateType = match ($overallState) {
        'degraded' => 'danger',
        'attention' => 'warning',
        'healthy' => 'success',
        default => 'info',
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
        ['key' => 'create', 'href' => route('monitorings.create'), 'visible' => $canCreateMonitoring, 'primary' => true],
        ['key' => 'maintenance', 'href' => route('maintenance.index'), 'visible' => $canManageMaintenance, 'primary' => false],
        ['key' => 'incidents', 'href' => route('incidents.analytics'), 'visible' => true, 'primary' => false],
        ['key' => 'notifications', 'href' => route('notifications.index'), 'visible' => true, 'primary' => false],
        ['key' => 'status_pages', 'href' => route('status-pages.index'), 'visible' => true, 'primary' => false],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ __('dashboard.title') }}</x-heading>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.description') }}</p>
        </div>

        <x-secondary-button :href="route('monitorings.index')" class="sm:ml-auto">
            {{ __('dashboard.open_monitorings') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        @if ($summary['total'] === 0)
            <x-container class="mx-auto max-w-3xl text-center" space="true">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-purple-100 text-2xl dark:bg-purple-400/10" aria-hidden="true">
                    <span>+</span>
                </div>
                <x-heading type="h2" class="mt-4">{{ __('dashboard.empty.title') }}</x-heading>
                <p class="mx-auto mt-3 max-w-xl text-sm text-gray-600 dark:text-gray-300">{{ __('dashboard.empty.description') }}</p>
                @if ($canCreateMonitoring)
                    <x-primary-button :href="route('monitorings.create')" class="mx-auto mt-6">
                        {{ __('dashboard.quick_actions.create') }}
                    </x-primary-button>
                @endif
            </x-container>
        @else
            <div class="space-y-4">
                <x-container space="true" class="border-l-4 {{ $stateType === 'danger' ? 'border-red-500' : ($stateType === 'warning' ? 'border-amber-500' : ($stateType === 'success' ? 'border-emerald-500' : 'border-purple-500')) }}">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :type="$stateType">{{ __('dashboard.recommended.label') }}</x-badge>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.total') }}: {{ $summary['total'] }}</span>
                            </div>
                            <x-heading type="h2" class="mt-3">
                                {{ __('dashboard.state.' . $overallState . '.title') }}
                            </x-heading>
                            <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                                @if ($overallState === 'degraded')
                                    {{ trans_choice('dashboard.state.degraded.description', $summary['down'], ['count' => $summary['down']]) }}
                                @elseif ($overallState === 'attention')
                                    {{ trans_choice('dashboard.state.attention.description', $summary['unknown'], ['count' => $summary['unknown']]) }}
                                @else
                                    {{ __('dashboard.state.' . $overallState . '.description') }}
                                @endif
                            </p>
                        </div>

                        <a href="{{ $recommendedHref }}" class="inline-flex shrink-0 items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                            {{ __('dashboard.recommended.' . $recommendedAction) }}
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                        @foreach ([
                            ['key' => 'healthy', 'value' => $summary['healthy'], 'href' => route('monitorings.index')],
                            ['key' => 'down', 'value' => $summary['down'], 'href' => route('incidents.analytics')],
                            ['key' => 'unknown', 'value' => $summary['unknown'], 'href' => route('monitorings.index', ['lifecycle' => 'active'])],
                            ['key' => 'paused', 'value' => $summary['paused'], 'href' => route('monitorings.index', ['lifecycle' => 'paused'])],
                            ['key' => 'maintenance', 'value' => $summary['maintenance'], 'href' => route('maintenance.index', ['maintenance_status' => 'active'])],
                            ['key' => 'total', 'value' => $summary['total'], 'href' => route('monitorings.index')],
                        ] as $metric)
                            <a href="{{ $metric['href'] }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 transition hover:border-purple-300 hover:bg-purple-50 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:border-purple-500 dark:hover:bg-gray-900">
                                <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.' . $metric['key']) }}</span>
                                <span class="mt-1 block text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $metric['value'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-container>

                <x-container space="true">
                    <x-heading type="h2" class="mb-4">{{ __('dashboard.quick_actions.heading') }}</x-heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($quickActions as $action)
                            @if ($action['visible'])
                                @if ($action['primary'])
                                    <x-primary-button :href="$action['href']">{{ __('dashboard.quick_actions.' . $action['key']) }}</x-primary-button>
                                @else
                                    <x-secondary-button :href="$action['href']">{{ __('dashboard.quick_actions.' . $action['key']) }}</x-secondary-button>
                                @endif
                            @endif
                        @endforeach
                    </div>
                </x-container>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <x-container space="true">
                        <x-heading type="h2">{{ __('dashboard.attention.heading') }}</x-heading>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.attention.description') }}</p>

                        @if ($attentionItems->isEmpty())
                            <p class="mt-5 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.attention.empty') }}</p>
                        @else
                            <div class="mt-5 divide-y divide-gray-200 dark:divide-gray-700">
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
                                        $itemBadge = match ($item['type']) {
                                            'incident' => 'danger',
                                            'down' => 'danger',
                                            'delivery' => 'danger',
                                            'stale' => 'warning',
                                            default => 'info',
                                        };
                                        $itemAction = $itemStatusPage && $itemIncident
                                            ? __('dashboard.attention.open_incident')
                                            : __('dashboard.attention.open');
                                    @endphp
                                    <a href="{{ $itemHref }}" class="group -mx-2 flex flex-col gap-3 rounded-md px-2 py-4 transition hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:hover:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $itemBadge === 'danger' ? 'bg-red-500' : ($itemBadge === 'warning' ? 'bg-amber-500' : 'bg-blue-500') }}" aria-hidden="true"></span>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $itemText }}</p>
                                                    <x-badge :type="$itemBadge">{{ __('dashboard.summary.' . (in_array($item['type'], ['incident', 'down', 'delivery'], true) ? 'down' : 'unknown')) }}</x-badge>
                                                </div>
                                                @if ($item['monitoring'])
                                                    <p class="mt-1 break-all text-sm text-gray-500 dark:text-gray-400">{{ $item['monitoring']->target }}</p>
                                                @endif
                                                @if ($itemStatusPage && $itemIncident)
                                                    <p class="mt-1 text-sm text-purple-700 dark:text-purple-300">
                                                        {{ __('dashboard.attention.status_page', ['name' => $itemStatusPage->name]) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="shrink-0 text-sm font-semibold text-purple-600 group-hover:text-purple-800 dark:text-purple-300 dark:group-hover:text-purple-200">
                                            {{ $itemAction }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </x-container>

                    <x-container space="true">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <x-heading type="h2">{{ __('dashboard.maintenance.heading') }}</x-heading>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.maintenance.description') }}</p>
                            </div>
                            <a href="{{ route('maintenance.index') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-800 dark:text-purple-300 dark:hover:text-purple-200">{{ __('dashboard.maintenance.open') }}</a>
                        </div>

                        @if ($maintenanceMonitorings->isEmpty())
                            <p class="mt-5 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.maintenance.empty') }}</p>
                        @else
                            <div class="mt-5 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($maintenanceMonitorings as $monitoring)
                                    @php $isActiveMaintenance = $monitoring->isUnderMaintenance(); @endphp
                                    <div class="flex items-center justify-between gap-3 py-4">
                                        <div class="min-w-0">
                                            <a href="{{ route('monitorings.show', $monitoring) }}" class="font-medium text-gray-900 hover:text-purple-600 dark:text-gray-100 dark:hover:text-purple-300">{{ $monitoring->name }}</a>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $monitoring->maintenance_from->locale(app()->getLocale())->isoFormat('L LT') }}</p>
                                        </div>
                                        <x-badge :type="$isActiveMaintenance ? 'warning' : 'info'">{{ $isActiveMaintenance ? __('dashboard.maintenance.active') : __('dashboard.maintenance.upcoming') }}</x-badge>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-container>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <x-container space="true">
                        <x-heading type="h2">{{ __('dashboard.incidents.heading') }}</x-heading>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.incidents.description') }}</p>

                        @if ($recentIncidents->isEmpty())
                            <p class="mt-5 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.incidents.empty') }}</p>
                        @else
                            <div class="mt-5 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($recentIncidents as $incident)
                                    <a href="{{ route('monitorings.show', $incident->monitoring) }}" class="group -mx-2 flex items-center justify-between gap-3 rounded-md px-2 py-4 transition hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:hover:bg-gray-900" id="incident-{{ $incident->id }}">
                                        <div class="min-w-0">
                                            <span class="font-medium text-gray-900 group-hover:text-purple-600 dark:text-gray-100 dark:group-hover:text-purple-300">{{ $incident->monitoring->name }}</span>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $incident->down_at->locale(app()->getLocale())->isoFormat('L LT') }}</p>
                                        </div>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <x-badge :type="$incident->up_at ? 'success' : 'danger'">{{ $incident->up_at ? __('dashboard.incidents.resolved') : __('dashboard.incidents.ongoing') }}</x-badge>
                                            <span class="hidden text-sm font-semibold text-purple-600 group-hover:text-purple-800 sm:inline dark:text-purple-300 dark:group-hover:text-purple-200">{{ __('dashboard.incidents.open') }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </x-container>

                    <x-container space="true">
                        <x-heading type="h2">{{ __('dashboard.trend.heading') }}</x-heading>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.trend.description') }}</p>

                        @if (collect($trend)->contains('has_data', true))
                            <div class="mt-5 grid grid-cols-7 items-end gap-2" aria-label="{{ __('dashboard.trend.heading') }}">
                                @foreach ($trend as $point)
                                    @php $barHeight = $point['has_data'] ? max(8, min(100, (float) $point['uptime_percentage'])) : 4; @endphp
                                    <div class="flex min-w-0 flex-col items-center gap-2">
                                        <span class="text-center text-xs font-medium text-gray-600 dark:text-gray-300">{{ $point['has_data'] ? __('dashboard.trend.uptime', ['value' => number_format($point['uptime_percentage'], 2)]) : '–' }}</span>
                                        <div class="flex h-32 w-full items-end rounded-md bg-gray-100 p-1 dark:bg-gray-900" title="{{ $point['date'] }}">
                                            <div class="w-full rounded-sm {{ $point['has_data'] && $point['uptime_percentage'] < 99 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="height: {{ $barHeight }}%" aria-hidden="true"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $point['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-5 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.trend.no_data') }}</p>
                        @endif
                    </x-container>
                </div>
            </div>
        @endif
    </x-main>
</x-app-layout>
