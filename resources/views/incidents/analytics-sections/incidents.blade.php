<div data-analytics-section-content>
    <section id="incident-analytics" class="space-y-4">
        <x-container>
            <x-heading type="h2">{{ __('incidents.analytics.sections.recent') }}</x-heading>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.definitions') }}</p>
            <form
                method="GET"
                action="{{ route('incidents.analytics') }}"
                class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5"
            >
                <x-select-input id="days" name="days"
                    ><option value="30" @selected($filters['days'] === 30)>
                        {{ __('incidents.analytics.filters.days_30') }}
                    </option>
                    <option value="90" @selected($filters['days'] === 90)>
                        {{ __('incidents.analytics.filters.days_90') }}
                    </option>
                    <option value="365" @selected($filters['days'] === 365)>
                        {{ __('incidents.analytics.filters.days_365') }}
                    </option></x-select-input>
                <x-select-input id="incident_type" name="incident_type"
                    ><option value="">{{ __('incidents.analytics.filters.all') }}</option>
                    @foreach ($incidentTypes as $type)
                        <option value="{{ $type->value }}" @selected($filters['incident_type'] === $type->value)>
                            {{ __('incidents.types.' . $type->value) }}
                        </option>
                    @endforeach
                </x-select-input>
                <x-select-input id="severity" name="severity"
                    ><option value="">{{ __('incidents.analytics.filters.all') }}</option>
                    @foreach ($severities as $severity)
                        <option value="{{ $severity->value }}" @selected($filters['severity'] === $severity->value)>
                            {{ __('incidents.severities.' . $severity->value) }}
                        </option>
                    @endforeach
                </x-select-input>
                <x-select-input id="customer_impact" name="customer_impact"
                    ><option value="">{{ __('incidents.analytics.filters.all') }}</option>
                    @foreach ($customerImpacts as $impact)
                        <option value="{{ $impact->value }}" @selected($filters['customer_impact'] === $impact->value)>
                            {{ __('incidents.customer_impacts.' . $impact->value) }}
                        </option>
                    @endforeach
                </x-select-input>
                <x-primary-button class="justify-center">{{ __('incidents.analytics.filters.apply') }}</x-primary-button>
                <x-text-input
                    id="affected_service"
                    name="affected_service"
                    :value="$filters['affected_service']"
                    class="sm:col-span-2 lg:col-span-5"
                    placeholder="{{ __('incidents.analytics.filters.affected_service') }}"
                />
            </form>
        </x-container>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([[$totalCount, 'incidents.analytics.metrics.total'], [$resolvedCount, 'incidents.analytics.metrics.resolved'], [$openCount, 'incidents.analytics.metrics.open'], [$mttrMinutes === null ? __('incidents.analytics.metrics.not_available') : __('incidents.analytics.metrics.minutes', ['value' => $mttrMinutes]), 'incidents.analytics.metrics.mttr']] as $metric)
                <x-container
                    ><p class="text-sm text-gray-500 dark:text-gray-400">{{ __($metric[1]) }}</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $metric[0] }}</p></x-container>
            @endforeach
        </div>

        <x-container>
            <x-heading type="h2">{{ __('incidents.analytics.sections.recent') }}</x-heading>
            @if ($incidentPaginator->isEmpty())
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.empty') }}</p>
            @else
                <div class="mt-4 overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
                    <table
                        data-incident-overview-table
                        class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700"
                    >
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($incidentPaginator as $incident)
                                <tr data-incident-row>
                                    <td class="px-4 py-3">
                                        {{ $incident->up_at ? __('incidents.analytics.table.resolved_state') : __('incidents.analytics.table.open_state') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <a
                                            href="{{ route('monitorings.show', $incident->monitoring) }}"
                                            class="font-bold text-gray-900 dark:text-gray-100"
                                        >{{ $incident->monitoring->name }}</a>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $incident->problem_description ?: ($incident->affected_service ?: __('incidents.analytics.unclassified')) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-date-time :value="$incident->down_at" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-pagination :paginator="$incidentPaginator" class="mt-4" />
            @endif
        </x-container>
    </section>
</div>
