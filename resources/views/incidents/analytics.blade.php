<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ __('incidents.analytics.title') }}</x-heading>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.description') }}</p>
        </div>
        <x-secondary-button :href="route('status-pages.index')">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <div class="space-y-4">
            <x-container>
                <form method="GET" action="{{ route('incidents.analytics') }}" class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <x-input-label for="days" :value="__('incidents.analytics.filters.period')" />
                        <x-select-input id="days" name="days" class="mt-1 w-full">
                            @foreach ([30, 90, 365] as $days)
                                <option value="{{ $days }}" @selected($filters['days'] === $days)>
                                    {{ __('incidents.analytics.filters.days_' . $days) }}
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
                    <div>
                        <x-input-label for="affected_service" :value="__('incidents.analytics.filters.affected_service')" />
                        <x-text-input id="affected_service" name="affected_service" :value="$filters['affected_service']" class="mt-1 w-full" />
                    </div>
                    <div class="md:col-span-2 lg:col-span-5">
                        <x-primary-button>{{ __('incidents.analytics.filters.apply') }}</x-primary-button>
                    </div>
                </form>
            </x-container>

            <div class="grid gap-4 md:grid-cols-4">
                @foreach ([
                    ['label' => __('incidents.analytics.metrics.total'), 'value' => $totalCount],
                    ['label' => __('incidents.analytics.metrics.resolved'), 'value' => $resolvedCount],
                    ['label' => __('incidents.analytics.metrics.open'), 'value' => $openCount],
                    ['label' => __('incidents.analytics.metrics.mttr'), 'value' => $mttrMinutes === null ? __('incidents.analytics.metrics.not_available') : __('incidents.analytics.metrics.minutes', ['value' => $mttrMinutes])],
                ] as $metric)
                    <x-container>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $metric['value'] }}</p>
                    </x-container>
                @endforeach
            </div>

            <x-container>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('incidents.analytics.definitions') }}</p>
            </x-container>

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ([
                    ['title' => __('incidents.analytics.sections.by_type'), 'items' => $byType, 'translation' => 'incidents.types.'],
                    ['title' => __('incidents.analytics.sections.by_severity'), 'items' => $bySeverity, 'translation' => 'incidents.severities.'],
                    ['title' => __('incidents.analytics.sections.by_impact'), 'items' => $byImpact, 'translation' => 'incidents.customer_impacts.'],
                    ['title' => __('incidents.analytics.sections.by_service'), 'items' => $byService, 'translation' => null],
                    ['title' => __('incidents.analytics.sections.recurrence'), 'items' => $repeatServices, 'translation' => null],
                ] as $section)
                    <x-container>
                        <x-heading type="h2">{{ $section['title'] }}</x-heading>
                        @if ($section['items']->isEmpty())
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.empty') }}</p>
                        @else
                            <dl class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($section['items'] as $key => $count)
                                    <div class="flex items-center justify-between gap-3 py-2 text-sm">
                                        <dt class="text-gray-700 dark:text-gray-300">
                                            {{ $section['translation'] ? __($section['translation'] . $key) : $key }}
                                        </dt>
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
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.empty') }}</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">{{ __('status_page.incident_metadata.type') }}</th>
                                    <th class="px-3 py-2">{{ __('status_page.incident_metadata.severity') }}</th>
                                    <th class="px-3 py-2">{{ __('status_page.incident_metadata.affected_service') }}</th>
                                    <th class="px-3 py-2">{{ __('monitoring.detail.incidents.incident.down_at') }}</th>
                                    <th class="px-3 py-2">{{ __('monitoring.detail.incidents.incident.up_at') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($incidents as $incident)
                                    <tr>
                                        <td class="px-3 py-2">{{ $incident->incident_type ? __('incidents.types.' . $incident->incident_type->value) : __('incidents.analytics.unclassified') }}</td>
                                        <td class="px-3 py-2">{{ $incident->severity ? __('incidents.severities.' . $incident->severity->value) : __('incidents.analytics.unclassified') }}</td>
                                        <td class="px-3 py-2">{{ $incident->affected_service ?: $incident->monitoring->name }}</td>
                                        <td class="px-3 py-2">{{ $incident->down_at->toDayDateTimeString() }}</td>
                                        <td class="px-3 py-2">{{ $incident->up_at?->toDayDateTimeString() ?? __('incidents.analytics.metrics.not_available') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-container>
        </div>
    </x-main>
</x-app-layout>
