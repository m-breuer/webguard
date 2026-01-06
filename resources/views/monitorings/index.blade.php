@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringLifecycleStatus;
    use App\Enums\MonitoringStatus;

    $monitoringIds = json_encode(collect($monitorings->items())->pluck('id'));
    $monitoringNames = json_encode($monitorings->pluck('name', 'id'));
    $monitoringTargets = json_encode($monitorings->pluck('target', 'id'));
    $monitoringTypes = json_encode($monitorings->pluck('type', 'id'));
    $monitoringStatusMap = json_encode($monitorings->pluck('status', 'id'));
    $monitoringPublicLabelMap = json_encode($monitorings->pluck('public_label_enabled', 'id'));
    $maintenanceStatusMap = json_encode($maintenanceStatusMap);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('monitoring.title') }}
        </x-heading>

        @if (!Auth::user()->isGuest() && $monitoringsTotal < Auth::user()->package->monitoring_limit)
            <x-primary-button :href="route('monitorings.create')" class="sm:ml-auto">
                {{ __('button.create') }}
            </x-primary-button>
        @endif
    </x-slot>

    <x-main>
        <x-container class="sm:flex sm:flex-wrap sm:items-center sm:justify-between sm:gap-4" space="true">
            <x-paragraph>
                <b>{{ __('monitoring.index.total.current') }}</b>: {{ $monitoringsTotal }}
                @if (Auth::user()->isMember())
                    {{ __('monitoring.index.total.of') }}
                    {{ Auth::user()->package->monitoring_limit }}
                @endif
            </x-paragraph>
        </x-container>

        <x-container class="ml-auto mr-auto" space="true">
            <form method="GET" action="{{ route('monitorings.index') }}"
                class="md:flex-wrapflex-col flex w-full flex-wrap-reverse justify-between gap-2 sm:flex-row sm:items-center sm:justify-start sm:gap-4">

                <div class="relative w-full max-w-md">
                    <x-text-input type="text" name="search" :value="request('search')"
                        placeholder="{{ __('search.fields.placeholder_monitoring') }}" />
                    @if (request('types'))
                        <x-text-input type="hidden" name="types" :value="is_array(request('types')) ? implode(',', request('types')) : request('types')" />
                    @endif
                    @if (request('lifecycle'))
                        <x-text-input type="hidden" name="lifecycle" :value="request('lifecycle')" />
                    @endif
                    @if (request('sort'))
                        <x-text-input type="hidden" name="sort" :value="request('sort')" />
                    @endif
                    @if (request('search'))
                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            &times;
                        </a>
                    @endif
                </div>

                <div x-data="{
                    selectedTypes: [],
                    init() {
                        const raw = '{{ request()->input('types', '') }}';
                        if (raw) {
                            this.selectedTypes = raw.split(',');
                        }
                    },
                    toggleType(type) {
                        if (this.selectedTypes.includes(type)) {
                            this.selectedTypes = this.selectedTypes.filter(t => t !== type);
                        } else {
                            this.selectedTypes.push(type);
                        }
                        this.updateUrl();
                    },
                    updateUrl() {
                        const url = new URL(window.location.href);
                        const params = new URLSearchParams(window.location.search);

                        if (this.selectedTypes.length > 0) {
                            params.set('types', this.selectedTypes.join(','));
                        } else {
                            params.delete('types');
                        }

                        url.search = params.toString();
                        window.location.href = url.toString();
                    }
                }" x-init="init()" class="flex flex-wrap gap-1">
                    @foreach (MonitoringType::cases() as $type)
                        <button type="button" @click="toggleType('{{ $type->value }}')"
                            :class="(selectedTypes.includes('{{ $type->value }}') ? 'bg-purple-500 text-white' :
                                'bg-gray-100 text-gray-700') +
                            ' rounded px-2 py-1  font-medium hover:bg-purple-100'">
                            {{ ucfirst($type->value) }}
                        </button>
                    @endforeach
                </div>

                <div class="flex gap-3 sm:ml-auto">
                    <div>
                        <div x-data="{
                            selectedStatus: '{{ request('lifecycle') ?? '' }}',
                            updateStatus() {
                                const url = new URL(window.location.href);
                                const params = new URLSearchParams(window.location.search);

                                if (this.selectedStatus) {
                                    params.set('lifecycle', this.selectedStatus);
                                } else {
                                    params.delete('lifecycle');
                                }

                                url.search = params.toString();
                                window.location.href = url.toString();
                            }
                        }" class="relative">
                            <label for="lifecycle-select" class="sr-only">{{ __('search.filter.lifecycle') }}</label>
                            <select id="lifecycle-select" x-model="selectedStatus" @change="updateStatus"
                                class="rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">{{ __('search.filter.all') }}</option>
                                @foreach (MonitoringLifecycleStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(request('lifecycle') === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="relative">
                        <div x-data="{
                            selectedSort: '{{ request('sort', 'name_asc') }}',
                            updateSort() {
                                const url = new URL(window.location.href);
                                const params = new URLSearchParams(window.location.search);

                                params.set('sort', this.selectedSort);

                                const types = params.get('types');
                                const search = params.get('search');

                                url.search = params.toString();
                                window.location.href = url.toString();
                            }
                        }">
                            <select x-model="selectedSort" @change="updateSort"
                                class="rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="name_asc">{{ __('search.filter.name.asc') }}</option>
                                <option value="name_desc">{{ __('search.filter.name.desc') }}</option>
                                <option value="created_desc">{{ __('search.filter.date.desc') }}</option>
                                <option value="created_asc">{{ __('search.filter.date.asc') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </x-container>

        @if ($errors->has('limit'))
            <x-container class="mb-4">
                {{ $errors->first('limit') }}
            </x-container>
        @endif

        <div x-data="monitoringCardLoader({{ $monitoringIds }}, {{ $monitoringNames }}, {{ $monitoringTargets }}, {{ $monitoringTypes }}, {{ $monitoringStatusMap }}, {{ $monitoringPublicLabelMap }}, {{ $maintenanceStatusMap }})" x-init="init()" x-cloak>
            <div x-show="monitoringIds.length === 0">
                <x-container class="text-center">
                    <x-heading type="h2">
                        {{ __('monitoring.no_monitoring.title') }}
                    </x-heading>
                    <x-paragraph>
                        {{ __('monitoring.no_monitoring.text') }}
                    </x-paragraph>
                    <x-primary-button :href="route('monitorings.create')">
                        {{ __('button.create') }}
                    </x-primary-button>
                </x-container>
            </div>

            <template x-for="id in monitoringIds" :key="id" x-show="Object.keys(statusMap).length > 0"
                x-cloak>
                <div x-bind:style="monitoringIds.length === 0 ? 'display:none' : ''">
                    <x-container space="true">
                        <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-3">
                            <div class="space-y-1">
                                <x-heading type="h2"
                                    x-text="monitoringNames[id] ?? '{{ __('monitoring.general.monitoring_id') }}'.replace(':id', id)"></x-heading>
                                <x-paragraph space="true" class="inline-flex items-center">
                                    <x-span x-text="monitoringTargets[id]"></x-span>
                                    <x-span x-text="'(' + monitoringTypes[id] + ')'" class="ml-2"></x-span>
                                    <template x-if="monitoringPublicLabelMap[id]">
                                        <a x-bind:href="'/label/' + id" target="_blank"
                                            class="ml-2 text-gray-400 hover:text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-4.5 0V6.75a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-.75.75H13.5a.75.75 0 0 1-.75-.75Z" />
                                            </svg>
                                        </a>
                                    </template>
                                </x-paragraph>
                                <div :id="'status-since-' + id">
                                    <div x-show="statusMap[id]" class="flex items-center gap-1">
                                        <x-span
                                            x-text="statusMap[id] === '{{ MonitoringStatus::UP->value }}' ? '🟢' : (statusMap[id] === '{{ MonitoringStatus::DOWN->value }}' ? '🔴' : '🟡')"></x-span>
                                        <x-span x-text="statusMap[id]?.toUpperCase()"></x-span>
                                        <x-span class="ml-1"
                                            x-text="sinceMap[id] ? '{{ __('monitoring.index.table.since') }} ' + sinceMap[id] : ''"></x-span>
                                    </div>
                                </div>
                            </div>
                            <div class="self-center">
                                <div :id="'monitoring-heatmap-' + id" class="flex gap-0.5 md:justify-center">
                                    <template x-for="i in 24" :key="i">
                                        <div class="h-6 w-3 animate-pulse rounded-sm bg-gray-300 dark:bg-gray-400">
                                        </div>
                                    </template>
                                </div>

                                <template x-if="monitoringStatusMap && monitoringStatusMap[id] === 'paused'">
                                    <div class="mt-1 sm:text-center">
                                        <x-badge type="warning">
                                            {{ __('monitoring.index.table.paused') }}
                                        </x-badge>
                                    </div>
                                </template>
                                <template x-if="maintenanceStatusMap && maintenanceStatusMap[id]">
                                    <div class="mt-1 sm:text-center">
                                        <x-badge type="info">
                                            {{ __('monitoring.index.table.maintenance') }}
                                        </x-badge>
                                    </div>
                                </template>
                            </div>
                            <div class="flex flex-wrap justify-start gap-2 self-center md:justify-end">
                                <a href="#" x-bind:href="'/monitorings/' + id"
                                    class="focus:outline-hidden inline-flex w-max cursor-pointer items-center rounded-md border border-purple-500 bg-white px-4 py-2 font-semibold uppercase tracking-widest text-purple-600 transition duration-150 ease-in-out hover:bg-purple-50 focus:bg-purple-50 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 active:bg-purple-100 dark:bg-gray-700 dark:text-purple-300">
                                    {{ __('button.show') }}
                                </a>
                            </div>
                        </div>
                    </x-container>
                </div>
            </template>

            {{ $monitorings->withQueryString()->links() }}
        </div>
    </x-main>

</x-app-layout>
