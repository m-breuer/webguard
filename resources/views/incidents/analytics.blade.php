@php
    $healthState = $overallState;
    $healthTitle = __('incidents.analytics.overview.health.' . $healthState);
    $healthType = match ($healthState) {
        'healthy' => 'success',
        'degraded' => 'danger',
        'attention' => 'warning',
        default => 'info',
    };
    $statusBadgeType = static fn (string $state): string => match ($state) {
        'healthy' => 'success',
        'degraded' => 'danger',
        'attention' => 'warning',
        default => 'info',
    };
    $trendPoints = collect($incidentTrend['points']);
    $trendLine = $trendPoints->map(fn (array $point): string => $point['x'] . ',' . $point['y'])->implode(' ');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-monitoring-operations-header />
    </x-slot>

    <x-main>
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            <x-form-modal name="status-page-form-modal" title="{{ __('status_page.title') }}"
                description="{{ __('status_page.form.components') }}" max-width="5xl">
                <div class="p-6" x-ref="content">
                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>

        <div id="service-operations" class="space-y-6">
            <section id="overview" aria-labelledby="service-health-heading">
                <x-container class="overflow-hidden">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="mt-1 inline-flex h-4 w-4 shrink-0 rounded-full {{ $healthType === 'success' ? 'bg-green-500' : ($healthType === 'danger' ? 'bg-red-500' : ($healthType === 'warning' ? 'bg-yellow-400' : 'bg-blue-500')) }}"
                                aria-hidden="true"></span>
                            <div>
                                <h2 id="service-health-heading" class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $healthTitle }}
                                </h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('incidents.analytics.overview.health.updated') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:grid-cols-4 lg:min-w-[34rem]">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.total') }}</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $serviceSummary['total'] }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.healthy') }}</p>
                                <p class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">{{ $serviceSummary['healthy'] }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.down') }}</p>
                                <p class="mt-1 text-lg font-semibold text-red-600 dark:text-red-400">{{ $serviceSummary['down'] }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.unknown') }}</p>
                                <p class="mt-1 text-lg font-semibold text-yellow-600 dark:text-yellow-400">{{ $serviceSummary['unknown'] }}</p>
                            </div>
                        </div>

                        <x-primary-button :href="route('status-pages.create')"
                            data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                            {{ __('incidents.analytics.overview.create_status_page') }}
                        </x-primary-button>
                    </div>
                </x-container>
            </section>

            <div class="grid gap-6 lg:grid-cols-5">
                <x-container class="overflow-hidden lg:col-span-3" id="monitoring-groups">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                        <div>
                            <x-heading type="h2">{{ __('incidents.analytics.overview.groups') }}</x-heading>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring_group.empty.text') }}</p>
                        </div>
                        <a href="{{ route('monitoring-groups.index') }}" class="shrink-0 text-sm font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-200">
                            {{ __('incidents.analytics.overview.view_all_groups') }}
                        </a>
                    </div>

                    @if ($monitoringGroups->isEmpty())
                        <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <p>{{ __('incidents.analytics.overview.empty_groups') }}</p>
                            @if (!Auth::user()->isDemo())
                                <x-secondary-button :href="route('monitoring-groups.create')" class="mt-4"
                                    data-form-modal-trigger data-form-modal-name="monitoring-group-form-modal">
                                    {{ __('button.create') }}
                                </x-secondary-button>
                            @endif
                        </div>
                    @else
                        <div class="mt-2 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($monitoringGroups as $group)
                                @php
                                    $monitoringGroup = $group['model'];
                                    $groupSummary = $group['summary'];
                                @endphp
                                <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('monitorings.index', ['group_id' => $monitoringGroup->id]) }}" class="truncate font-semibold text-gray-900 hover:text-purple-700 dark:text-gray-100 dark:hover:text-purple-300">
                                                {{ $monitoringGroup->name }}
                                            </a>
                                            <x-badge :type="$statusBadgeType($groupSummary['state'])">
                                                {{ __('incidents.analytics.overview.status.' . $groupSummary['state']) }}
                                            </x-badge>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ trans_choice('monitoring_group.monitorings_count', $groupSummary['total'], ['count' => $groupSummary['total']]) }}
                                            <span class="mx-1">·</span>
                                            {{ $groupSummary['healthy'] }} {{ __('dashboard.summary.healthy') }}
                                            @if ($groupSummary['down'] > 0)
                                                <span class="mx-1">·</span>
                                                {{ $groupSummary['down'] }} {{ __('dashboard.summary.down') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                        @if (!Auth::user()->isDemo())
                                            <x-secondary-button :href="route('monitoring-groups.edit', $monitoringGroup)"
                                                data-form-modal-trigger data-form-modal-name="monitoring-group-form-modal" :icon-only="true"
                                                title="{{ __('button.edit') }}" aria-label="{{ __('button.edit') }}">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </x-secondary-button>
                                        @endif
                                        <a href="{{ route('monitorings.index', ['group_id' => $monitoringGroup->id]) }}"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md text-purple-700 transition hover:bg-purple-50 hover:text-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-purple-300 dark:hover:bg-purple-950/40 dark:hover:text-purple-200"
                                            title="{{ __('incidents.analytics.overview.view_group') }}" aria-label="{{ __('incidents.analytics.overview.view_group') }}">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-container>

                <x-container class="overflow-hidden lg:col-span-2" id="status-pages">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                        <div>
                            <x-heading type="h2">{{ __('incidents.analytics.overview.status_pages') }}</x-heading>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('status_page.empty.text') }}</p>
                        </div>
                        <a href="{{ route('status-pages.index') }}" class="shrink-0 text-sm font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-200">
                            {{ __('incidents.analytics.overview.view_all_status_pages') }}
                        </a>
                    </div>

                    @if ($statusPages->isEmpty())
                        <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <p>{{ __('incidents.analytics.overview.empty_status_pages') }}</p>
                            @if (!Auth::user()->isDemo())
                                <x-secondary-button :href="route('status-pages.create')" class="mt-4"
                                    data-form-modal-trigger data-form-modal-name="status-page-form-modal">
                                    {{ __('button.create') }}
                                </x-secondary-button>
                            @endif
                        </div>
                    @else
                        <div class="mt-2 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($statusPages as $statusPageSummary)
                                @php
                                    $statusPage = $statusPageSummary['model'];
                                    $statusPageHealth = $statusPageSummary['summary'];
                                @endphp
                                <div class="flex flex-col gap-3 py-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <a href="{{ route('status-pages.show', $statusPage) }}" class="truncate font-semibold text-gray-900 hover:text-purple-700 dark:text-gray-100 dark:hover:text-purple-300">
                                                    {{ $statusPage->name }}
                                                </a>
                                                @if ($statusPage->is_public)
                                                    <a href="{{ route('public-status-pages.show', $statusPage) }}" target="_blank" rel="noopener"
                                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-purple-600 transition hover:bg-purple-100 hover:text-purple-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-purple-300 dark:hover:bg-purple-950/40 dark:hover:text-purple-200"
                                                        title="{{ __('status_page.detail.open_public_page') }}"
                                                        aria-label="{{ __('status_page.detail.open_public_page') }}">
                                                        <x-icon name="external-link" class="h-4 w-4" />
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                                                    {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                                                </x-badge>
                                                <span>{{ trans_choice('status_page.components_count', $statusPage->components_count, ['count' => $statusPage->components_count]) }}</span>
                                            </div>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center gap-1 text-sm font-medium {{ $statusPageHealth['state'] === 'healthy' ? 'text-green-600 dark:text-green-400' : ($statusPageHealth['state'] === 'degraded' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400') }}">
                                            <span class="h-2 w-2 rounded-full bg-current" aria-hidden="true"></span>
                                            {{ __('incidents.analytics.overview.status.' . $statusPageHealth['state']) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400">
                                        <span>
                                            {{ $statusPageHealth['total'] }} {{ __('incidents.analytics.overview.services') }}
                                            <span class="mx-1">·</span>
                                            {{ $statusPageHealth['down'] }} {{ __('dashboard.summary.down') }}
                                        </span>
                                        <a href="{{ route('status-pages.show', $statusPage) }}"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md text-purple-700 transition hover:bg-purple-50 hover:text-purple-900 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-purple-300 dark:hover:bg-purple-950/40 dark:hover:text-purple-200"
                                            title="{{ __('incidents.analytics.overview.view_status_page') }}" aria-label="{{ __('incidents.analytics.overview.view_status_page') }}">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-container>
            </div>

            <section id="incident-analytics" aria-labelledby="incident-analytics-heading" class="scroll-mt-6 space-y-4">
                <x-container>
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <x-heading type="h2" id="incident-analytics-heading">{{ __('incidents.analytics.sections.recent') }}</x-heading>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.definitions') }}</p>
                        </div>
                        <form method="GET" action="{{ route('incidents.analytics') }}" class="grid w-full gap-3 sm:grid-cols-2 lg:max-w-4xl lg:grid-cols-5">
                            <div>
                                <x-input-label for="days" :value="__('incidents.analytics.filters.period')" />
                                <x-select-input id="days" name="days" class="mt-1 w-full">
                                    @foreach ([30, 90, 365] as $daysOption)
                                        <option value="{{ $daysOption }}" @selected($filters['days'] === $daysOption)>
                                            {{ __('incidents.analytics.filters.days_' . $daysOption) }}
                                        </option>
                                    @endforeach
                                </x-select-input>
                            </div>
                            <div>
                                <x-input-label for="incident_type" :value="__('incidents.analytics.filters.type')" />
                                <x-select-input id="incident_type" name="incident_type" class="mt-1 w-full">
                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                    @foreach ($incidentTypes as $type)
                                        <option value="{{ $type->value }}" @selected($filters['incident_type'] === $type->value)>
                                            {{ __('incidents.types.' . $type->value) }}
                                        </option>
                                    @endforeach
                                </x-select-input>
                            </div>
                            <div>
                                <x-input-label for="severity" :value="__('incidents.analytics.filters.severity')" />
                                <x-select-input id="severity" name="severity" class="mt-1 w-full">
                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                    @foreach ($severities as $severity)
                                        <option value="{{ $severity->value }}" @selected($filters['severity'] === $severity->value)>
                                            {{ __('incidents.severities.' . $severity->value) }}
                                        </option>
                                    @endforeach
                                </x-select-input>
                            </div>
                            <div>
                                <x-input-label for="customer_impact" :value="__('incidents.analytics.filters.customer_impact')" />
                                <x-select-input id="customer_impact" name="customer_impact" class="mt-1 w-full">
                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                    @foreach ($customerImpacts as $impact)
                                        <option value="{{ $impact->value }}" @selected($filters['customer_impact'] === $impact->value)>
                                            {{ __('incidents.customer_impacts.' . $impact->value) }}
                                        </option>
                                    @endforeach
                                </x-select-input>
                            </div>
                            <div class="flex items-end">
                                <x-primary-button class="w-full justify-center">{{ __('incidents.analytics.filters.apply') }}</x-primary-button>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-5">
                                <x-input-label for="affected_service" :value="__('incidents.analytics.filters.affected_service')" />
                                <x-text-input id="affected_service" name="affected_service" :value="$filters['affected_service']" class="mt-1 w-full" />
                            </div>
                        </form>
                    </div>
                </x-container>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => __('incidents.analytics.metrics.total'), 'value' => $totalCount, 'class' => 'text-gray-900 dark:text-gray-100'],
                        ['label' => __('incidents.analytics.metrics.resolved'), 'value' => $resolvedCount, 'class' => 'text-green-600 dark:text-green-400'],
                        ['label' => __('incidents.analytics.metrics.open'), 'value' => $openCount, 'class' => 'text-red-600 dark:text-red-400'],
                        ['label' => __('incidents.analytics.metrics.mttr'), 'value' => $mttrMinutes === null ? __('incidents.analytics.metrics.not_available') : __('incidents.analytics.metrics.minutes', ['value' => $mttrMinutes]), 'class' => 'text-gray-900 dark:text-gray-100'],
                    ] as $metric)
                        <x-container>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold {{ $metric['class'] }}">{{ $metric['value'] }}</p>
                        </x-container>
                    @endforeach
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <x-container class="lg:col-span-2">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <x-heading type="h2">{{ __('incidents.analytics.overview.trend') }}</x-heading>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.filters.days_' . $filters['days']) }}</p>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $incidentTrend['max'] }} {{ __('incidents.analytics.metrics.total') }}</span>
                        </div>

                        @if ($trendPoints->isEmpty() || $totalCount === 0)
                            <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.overview.trend_empty') }}</p>
                        @else
                            <div class="mt-6">
                                <svg viewBox="0 0 100 86" class="h-56 w-full overflow-visible" role="img" aria-label="{{ __('incidents.analytics.overview.trend') }}">
                                    <line x1="0" y1="20" x2="100" y2="20" class="stroke-gray-200 dark:stroke-gray-700" stroke-dasharray="1 2" />
                                    <line x1="0" y1="49" x2="100" y2="49" class="stroke-gray-200 dark:stroke-gray-700" stroke-dasharray="1 2" />
                                    <line x1="0" y1="78" x2="100" y2="78" class="stroke-gray-300 dark:stroke-gray-600" />
                                    <polyline points="0,78 {{ $trendLine }} 100,78" fill="rgba(124, 58, 237, 0.08)" class="stroke-none" />
                                    <polyline points="{{ $trendLine }}" fill="none" class="stroke-purple-600 dark:stroke-purple-400" stroke-width="1.3" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
                                    @foreach ($trendPoints as $point)
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.5" class="fill-purple-600 dark:fill-purple-400" />
                                    @endforeach
                                </svg>
                                <div class="mt-2 flex justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    @foreach ($trendPoints as $point)
                                        <span>{{ $point['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </x-container>

                    <x-container>
                        <x-heading type="h2">{{ __('incidents.analytics.overview.recurring') }}</x-heading>
                        @if ($repeatServices->isEmpty())
                            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.overview.recurring_empty') }}</p>
                        @else
                            <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($repeatServices->take(5) as $service => $count)
                                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                                        <span class="truncate text-gray-700 dark:text-gray-300">{{ $service }}</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-container>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ([
                        ['title' => __('incidents.analytics.sections.by_type'), 'items' => $byType, 'translation' => 'incidents.types.'],
                        ['title' => __('incidents.analytics.sections.by_severity'), 'items' => $bySeverity, 'translation' => 'incidents.severities.'],
                        ['title' => __('incidents.analytics.sections.by_impact'), 'items' => $byImpact, 'translation' => 'incidents.customer_impacts.'],
                    ] as $section)
                        <x-container>
                            <x-heading type="h2">{{ $section['title'] }}</x-heading>
                            @if ($section['items']->isEmpty())
                                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.empty') }}</p>
                            @else
                                <dl class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($section['items'] as $key => $count)
                                        <div class="flex items-center justify-between gap-3 py-2 text-sm">
                                            <dt class="text-gray-700 dark:text-gray-300">{{ __($section['translation'] . $key) }}</dt>
                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $count }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </x-container>
                    @endforeach
                </div>

                <x-container>
                    <x-heading type="h2">{{ __('incidents.analytics.sections.recent') }}</x-heading>
                    @if ($incidents->isEmpty())
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.empty') }}</p>
                    @else
                        <div class="mt-4 overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
                            <table data-incident-overview-table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead>
                                    <tr class="bg-gray-50/80 text-left text-xs font-bold uppercase tracking-[0.12em] text-gray-500 dark:bg-gray-900/30 dark:text-gray-400">
                                        <th class="px-4 py-3">{{ __('incidents.analytics.table.status') }}</th>
                                        <th class="px-4 py-3">{{ __('incidents.analytics.table.monitoring') }}</th>
                                        <th class="px-4 py-3">{{ __('incidents.analytics.table.root_cause') }}</th>
                                        <th class="px-4 py-3">{{ __('incidents.analytics.table.started') }}</th>
                                        <th class="px-4 py-3">{{ __('incidents.analytics.table.resolved') }}</th>
                                        <th class="px-4 py-3">{{ __('incidents.analytics.table.duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($incidents as $incident)
                                        @php($incidentDuration = $incident->up_at ? $incident->down_at->diffForHumans($incident->up_at, true) : __('incidents.analytics.table.ongoing'))
                                        <tr data-incident-row class="group transition hover:bg-purple-50/50 dark:hover:bg-purple-950/20">
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex items-center gap-2 font-semibold {{ $incident->up_at ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                                    <span class="h-2.5 w-2.5 rounded-full {{ $incident->up_at ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                                    {{ $incident->up_at ? __('incidents.analytics.table.resolved_state') : __('incidents.analytics.table.open_state') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('monitorings.show', $incident->monitoring) }}" class="font-bold text-gray-900 hover:text-purple-700 dark:text-gray-100 dark:hover:text-purple-300">{{ $incident->monitoring->name }}</a>
                                                <span class="mt-1 block max-w-[14rem] truncate text-xs text-gray-500 dark:text-gray-400">{{ $incident->monitoring->target }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $incident->problem_description ?: ($incident->affected_service ?: __('incidents.analytics.unclassified')) }}</span>
                                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $incident->incident_type ? __('incidents.types.' . $incident->incident_type->value) : __('incidents.analytics.unclassified') }} · {{ $incident->severity ? __('incidents.severities.' . $incident->severity->value) : __('incidents.analytics.unclassified') }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $incident->down_at->toDayDateTimeString() }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $incident->up_at?->toDayDateTimeString() ?? __('incidents.analytics.metrics.not_available') }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $incidentDuration }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-container>

                <details class="rounded-lg border border-gray-200 bg-white px-6 py-4 text-sm text-gray-600 shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100">{{ __('incidents.analytics.overview.definitions_toggle') }}</summary>
                    <p class="mt-3">{{ __('incidents.analytics.definitions') }}</p>
                </details>
            </section>
        </div>

        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
            <x-form-modal name="monitoring-group-form-modal" title="{{ __('monitoring_group.title') }}"
                description="{{ __('monitoring_group.form.monitorings') }}" max-width="3xl">
                <div class="p-6" x-ref="content">
                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
