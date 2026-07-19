@php
    use App\Enums\MonitoringType;
    use App\Enums\MonitoringLifecycleStatus;
    use App\Enums\MonitoringStatus;

    $monitoringIds = json_encode(collect($monitorings->items())->pluck('id'));
    $summaryMonitoringIds = json_encode($summaryMonitoringIds);
    $monitoringNames = json_encode($monitorings->pluck('name', 'id'));
    $monitoringTargets = json_encode($monitorings->pluck('target', 'id'));
    $monitoringTypes = json_encode($monitorings->getCollection()->mapWithKeys(fn ($monitoring) => [
        $monitoring->id => __('monitoring.types.' . $monitoring->type->value),
    ]));
    $monitoringStatusMap = json_encode($monitorings->pluck('status', 'id'));
    $monitoringPublicLabelMap = json_encode($monitorings->pluck('public_label_enabled', 'id'));
    $maintenanceStatusMap = json_encode($maintenanceStatusMap);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-monitoring-operations-header>
            @if ($canCreateMonitoring)
                <x-slot name="actions">
                    <x-primary-button :href="route('monitorings.create')"
                        data-form-modal-trigger data-form-modal-name="monitoring-form-modal">
                        {{ __('button.create') }}
                    </x-primary-button>
                </x-slot>
            @endif
        </x-monitoring-operations-header>
    </x-slot>

    <x-main>
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
        <x-container class="sm:flex sm:flex-wrap sm:items-center sm:justify-between sm:gap-4" space="true">
            <x-paragraph>
                <b>{{ __('monitoring.index.total.current') }}</b>: {{ $monitoringsTotal }}
                @if ($currentUser->isMember())
                    {{ __('monitoring.index.total.of') }}
                    {{ $monitoringLimit }}
                @endif
            </x-paragraph>
        </x-container>

        <x-container class="ml-auto mr-auto" space="true">
            <div class="mb-4">
                <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('monitoring.index.filters.presets') }}
                </p>
                <nav class="flex flex-wrap gap-2" aria-label="{{ __('monitoring.index.filters.presets') }}">
                    @foreach ([
                        ['label' => __('monitoring.index.filters.all'), 'query' => [], 'active' => ! request()->hasAny(['search', 'types', 'lifecycle', 'group_id', 'team_id', 'ownership', 'health', 'maintenance'])],
                        ['label' => __('monitoring.index.filters.attention'), 'query' => ['health' => 'down,unknown'], 'active' => request('health') === 'down,unknown'],
                        ['label' => __('monitoring.index.filters.paused'), 'query' => ['lifecycle' => 'paused'], 'active' => request('lifecycle') === 'paused'],
                        ['label' => __('monitoring.index.filters.maintenance'), 'query' => ['maintenance' => 'active'], 'active' => request('maintenance') === 'active'],
                    ] as $preset)
                        <a href="{{ route('monitorings.index', $preset['query']) }}"
                            @class([
                                'rounded-full px-3 py-1.5 text-sm font-medium transition',
                                'bg-purple-600 text-white' => $preset['active'],
                                'bg-gray-100 text-gray-700 hover:bg-purple-100 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' => ! $preset['active'],
                            ])>
                            {{ $preset['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <form method="GET" action="{{ route('monitorings.index') }}"
                class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-start sm:gap-4">

                <div class="relative w-full sm:max-w-md">
                    <x-text-input type="text" name="search" :value="request('search')"
                        placeholder="{{ __('search.fields.placeholder_monitoring') }}" />
                    @if (request('types'))
                        <x-text-input type="hidden" name="types" :value="is_array(request('types')) ? implode(',', request('types')) : request('types')" />
                    @endif
                    @if (request('lifecycle'))
                        <x-text-input type="hidden" name="lifecycle" :value="request('lifecycle')" />
                    @endif
                    @if (request('group_id'))
                        <x-text-input type="hidden" name="group_id" :value="request('group_id')" />
                    @endif
                    @if (request('team_id'))
                        <x-text-input type="hidden" name="team_id" :value="request('team_id')" />
                    @endif
                    @if (request('ownership'))
                        <x-text-input type="hidden" name="ownership" :value="request('ownership')" />
                    @endif
                    @if (request('health'))
                        <x-text-input type="hidden" name="health" :value="request('health')" />
                    @endif
                    @if (request('maintenance'))
                        <x-text-input type="hidden" name="maintenance" :value="request('maintenance')" />
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

                <details @if (request('types') || request('lifecycle') || request('group_id') || request('team_id') || request('ownership') || request('health') || request('maintenance') || request('sort')) open @endif
                    class="w-full rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <summary class="cursor-pointer font-semibold text-gray-700 dark:text-gray-200">
                        {{ __('monitoring.index.filters.advanced') }}
                    </summary>

                    <div class="mt-3 space-y-3">
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
                }" x-init="init()" class="flex w-full flex-wrap gap-2">
                    @foreach (MonitoringType::cases() as $type)
                        <button type="button" @click="toggleType('{{ $type->value }}')"
                            :class="(selectedTypes.includes('{{ $type->value }}') ? 'bg-purple-500 text-white' :
                                'bg-gray-100 text-gray-700') +
                            ' rounded px-2 py-1 text-sm font-medium hover:bg-purple-100'">
                            {{ __('monitoring.types.' . $type->value) }}
                        </button>
                    @endforeach
                </div>

                <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:flex md:w-auto md:flex-wrap md:gap-3">
                    <div class="min-w-0">
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
                                class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 md:w-auto">
                                <option value="">{{ __('search.filter.all') }}</option>
                                @foreach (MonitoringLifecycleStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(request('lifecycle') === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div x-data="{
                            selectedGroup: '{{ request('group_id') ?? '' }}',
                            updateGroup() {
                                const url = new URL(window.location.href);
                                const params = new URLSearchParams(window.location.search);

                                if (this.selectedGroup) {
                                    params.set('group_id', this.selectedGroup);
                                } else {
                                    params.delete('group_id');
                                }

                                url.search = params.toString();
                                window.location.href = url.toString();
                            }
                        }" class="relative">
                            <label for="group-select" class="sr-only">{{ __('monitoring_group.filter.label') }}</label>
                            <select id="group-select" x-model="selectedGroup" @change="updateGroup"
                                class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 md:w-auto">
                                <option value="">{{ __('monitoring_group.filter.all') }}</option>
                                @foreach ($monitoringGroups as $monitoringGroup)
                                    <option value="{{ $monitoringGroup->id }}" @selected(request('group_id') === $monitoringGroup->id)>
                                        {{ $monitoringGroup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div x-data="{
                            selectedOwnership: '{{ request('ownership') ?? '' }}',
                            updateOwnership() {
                                const url = new URL(window.location.href);
                                const params = new URLSearchParams(window.location.search);

                                if (this.selectedOwnership) {
                                    params.set('ownership', this.selectedOwnership);
                                } else {
                                    params.delete('ownership');
                                }

                                url.search = params.toString();
                                window.location.href = url.toString();
                            }
                        }" class="relative">
                            <label for="ownership-select" class="sr-only">{{ __('team.ownership.select_label') }}</label>
                            <select id="ownership-select" x-model="selectedOwnership" @change="updateOwnership"
                                class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 md:w-auto">
                                <option value="">{{ __('search.filter.all') }}</option>
                                <option value="private" @selected(request('ownership') === 'private')>{{ __('team.ownership.private') }}</option>
                                <option value="team" @selected(request('ownership') === 'team')>{{ __('team.ownership.team') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div x-data="{
                            selectedTeam: '{{ request('team_id') ?? '' }}',
                            updateTeam() {
                                const url = new URL(window.location.href);
                                const params = new URLSearchParams(window.location.search);

                                if (this.selectedTeam) {
                                    params.set('team_id', this.selectedTeam);
                                } else {
                                    params.delete('team_id');
                                }

                                url.search = params.toString();
                                window.location.href = url.toString();
                            }
                        }" class="relative">
                            <label for="team-select" class="sr-only">{{ __('team.title') }}</label>
                            <select id="team-select" x-model="selectedTeam" @change="updateTeam"
                                class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 md:w-auto">
                                <option value="">{{ __('team.title') }}</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected(request('team_id') === $team->id)>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="relative min-w-0">
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
                            <label for="sort-select" class="sr-only">{{ __('search.filter.heading') }}</label>
                            <select id="sort-select" x-model="selectedSort" @change="updateSort"
                                class="w-full rounded-md border border-gray-300 p-2 pr-8 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 md:w-auto">
                                <option value="name_asc">{{ __('search.filter.name.asc') }}</option>
                                <option value="name_desc">{{ __('search.filter.name.desc') }}</option>
                                <option value="created_desc">{{ __('search.filter.date.desc') }}</option>
                                <option value="created_asc">{{ __('search.filter.date.asc') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                    </div>
                </details>
            </form>

            @if (request()->hasAny(['search', 'types', 'lifecycle', 'group_id', 'team_id', 'ownership', 'health', 'maintenance']))
                <div class="mt-4 flex flex-wrap items-center gap-2" aria-label="{{ __('monitoring.index.filters.active') }}">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('monitoring.index.filters.active') }}:</span>
                    @if (request('search'))
                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ request('search') }} &times;
                        </a>
                    @endif
                    @if (request('health'))
                        <a href="{{ request()->fullUrlWithQuery(['health' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ __('monitoring.index.filters.health') }}: {{ request('health') }} &times;
                        </a>
                    @endif
                    @if (request('lifecycle'))
                        <a href="{{ request()->fullUrlWithQuery(['lifecycle' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ ucfirst(request('lifecycle')) }} &times;
                        </a>
                    @endif
                    @if (request('maintenance'))
                        <a href="{{ request()->fullUrlWithQuery(['maintenance' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ __('monitoring.index.filters.maintenance_active') }} &times;
                        </a>
                    @endif
                    @if (request('types'))
                        <a href="{{ request()->fullUrlWithQuery(['types' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ __('monitoring.index.table.type') }}: {{ request('types') }} &times;
                        </a>
                    @endif
                    @if (request('group_id'))
                        <a href="{{ request()->fullUrlWithQuery(['group_id' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ __('monitoring_group.filter.label') }} &times;
                        </a>
                    @endif
                    @if (request('team_id'))
                        <a href="{{ request()->fullUrlWithQuery(['team_id' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ __('team.title') }} &times;
                        </a>
                    @endif
                    @if (request('ownership'))
                        <a href="{{ request()->fullUrlWithQuery(['ownership' => null]) }}" class="rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                            {{ __('team.ownership.select_label') }} &times;
                        </a>
                    @endif
                    <a href="{{ route('monitorings.index') }}" class="ml-auto text-sm font-semibold text-purple-700 underline dark:text-purple-300">
                        {{ __('monitoring.index.filters.clear') }}
                    </a>
                </div>
            @endif
        </x-container>

        @if ($errors->has('limit'))
            <x-container class="mb-4">
                {{ $errors->first('limit') }}
            </x-container>
        @endif

        <div x-data="monitoringCardLoader({{ $monitoringIds }}, {{ $monitoringNames }}, {{ $monitoringTargets }}, {{ $monitoringTypes }}, {{ $monitoringStatusMap }}, {{ $monitoringPublicLabelMap }}, {{ $maintenanceStatusMap }}, {{ $summaryMonitoringIds }})">
            <div x-show="monitoringIds.length > 0" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="min-w-0 space-y-6">
                    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-5 dark:border-gray-700 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <x-heading type="h2">{{ __('monitoring.index.workspace.active') }}</x-heading>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('monitoring.index.workspace.subtitle') }}</p>
                            </div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('monitoring.index.total.current') }}: {{ $monitoringsTotal }}
                                @if ($currentUser->isMember())
                                    <span class="text-gray-400">·</span> {{ __('monitoring.index.workspace.capacity', ['current' => $privateMonitoringsTotal, 'limit' => $monitoringLimit]) }}
                                @endif
                            </p>
                        </div>

                        <div class="hidden grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_11rem_7rem_auto] gap-4 border-b border-gray-100 bg-gray-50/80 px-5 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-500 lg:grid">
                            <span>{{ __('monitoring.index.table.name') }}</span>
                            <span>{{ __('monitoring.index.workspace.target') }}</span>
                            <span>{{ __('monitoring.index.workspace.history') }}</span>
                            <span>{{ __('monitoring.index.workspace.status') }}</span>
                            <span class="sr-only">{{ __('monitoring.index.workspace.actions') }}</span>
                        </div>

                        <template x-for="id in monitoringIds" :key="id" x-cloak>
                            <article class="group border-b border-gray-100 px-5 py-4 last:border-b-0 hover:bg-purple-50/30 dark:border-gray-700 dark:hover:bg-purple-950/20">
                                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_11rem_7rem_auto] lg:items-center">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-gray-300" :class="{
                                                'bg-emerald-500': statusMap[id] === '{{ MonitoringStatus::UP->value }}',
                                                'bg-red-500': statusMap[id] === '{{ MonitoringStatus::DOWN->value }}',
                                                'bg-amber-500': statusMap[id] === '{{ MonitoringStatus::UNKNOWN->value }}' || !statusMap[id]
                                            }" aria-hidden="true"></span>
                                            <a href="#" x-bind:href="'/monitorings/' + id" class="truncate font-semibold text-gray-900 hover:text-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-gray-100 dark:hover:text-purple-300" x-text="monitoringNames[id] ?? '{{ __('monitoring.general.monitoring_id') }}'.replace(':id', id)"></a>
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200" x-text="monitoringTypes[id]"></span>
                                            <template x-if="monitoringPublicLabelMap[id]">
                                                <a x-bind:href="'/label/' + id" target="_blank" class="text-purple-600 hover:text-purple-800 dark:text-purple-300" aria-label="{{ __('monitoring.index.workspace.view') }}">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-4.5 0V6.75a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-.75.75H13.5a.75.75 0 0 1-.75-.75Z" /></svg>
                                                </a>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400 lg:hidden">{{ __('monitoring.index.workspace.target') }}</p>
                                        <p class="truncate text-sm text-gray-600 dark:text-gray-300" x-text="monitoringTargets[id]"></p>
                                        <p class="mt-1 text-xs text-gray-400" x-show="sinceMap[id]" x-text="sinceMap[id] ? '{{ __('monitoring.index.table.since') }} ' + sinceMap[id] : ''"></p>
                                    </div>

                                    <div>
                                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400 lg:hidden">{{ __('monitoring.index.workspace.history') }}</p>
                                        <div :id="'monitoring-heatmap-' + id" class="flex gap-0.5" :aria-label="'{{ __('monitoring.index.workspace.history') }}'"><template x-for="i in 24" :key="i"><span class="h-6 w-2.5 animate-pulse rounded-xs bg-gray-200 dark:bg-gray-600"></span></template></div>
                                    </div>

                                    <div>
                                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400 lg:hidden">{{ __('monitoring.index.workspace.status') }}</p>
                                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold" :class="{
                                            'text-emerald-600 dark:text-emerald-400': statusMap[id] === '{{ MonitoringStatus::UP->value }}',
                                            'text-red-600 dark:text-red-400': statusMap[id] === '{{ MonitoringStatus::DOWN->value }}',
                                            'text-amber-600 dark:text-amber-400': statusMap[id] === '{{ MonitoringStatus::UNKNOWN->value }}' || !statusMap[id]
                                        }">
                                            <span x-text="statusMap[id] === '{{ MonitoringStatus::UP->value }}' ? '{{ __('monitoring.index.filters.health_up') }}' : (statusMap[id] === '{{ MonitoringStatus::DOWN->value }}' ? '{{ __('monitoring.index.filters.health_down') }}' : '{{ __('monitoring.index.filters.health_unknown') }}')"></span>
                                        </span>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <template x-if="monitoringStatusMap && monitoringStatusMap[id] === 'paused'"><x-badge type="warning">{{ __('monitoring.index.table.paused') }}</x-badge></template>
                                            <template x-if="maintenanceStatusMap && maintenanceStatusMap[id]"><x-badge type="info">{{ __('monitoring.index.table.maintenance') }}</x-badge></template>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 lg:justify-end">
                                        <a href="#" x-bind:href="'/monitorings/' + id" class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-purple-300 hover:text-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:border-gray-600 dark:text-gray-200 dark:hover:border-purple-500 dark:hover:text-purple-300">{{ __('monitoring.index.workspace.view') }}</a>
                                        <a href="#" x-bind:href="'/monitorings/' + id + '/edit'" data-form-modal-trigger data-form-modal-name="monitoring-form-modal" class="inline-flex items-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">{{ __('button.edit') }}</a>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </section>

                    <div class="flex justify-center">
                        {{ $monitorings->withQueryString()->links() }}
                    </div>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start" aria-label="{{ __('monitoring.index.table.summary') }}">
                    <section class="rounded-2xl border border-purple-200 bg-purple-50 p-5 shadow-sm dark:border-purple-900/60 dark:bg-purple-950/30">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-600 dark:text-purple-300">{{ __('monitoring.index.workspace.all_systems') }}</p>
                                <p class="mt-2 text-lg font-bold text-gray-900 dark:text-gray-100" x-text="summaryReady ? (attentionCount > 0 ? '{{ __('monitoring.index.workspace.attention_state') }}' : '{{ __('monitoring.index.workspace.operational') }}') : '{{ __('monitoring.index.table.summary_loading') }}'"></p>
                            </div>
                            <span class="mt-1 h-3 w-3 rounded-full bg-gray-300" :class="attentionCount > 0 ? 'bg-red-500' : 'bg-emerald-500'" aria-hidden="true"></span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3 border-t border-purple-200 pt-4 dark:border-purple-900/60">
                            <div><p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="summaryReady ? healthyCount : '—'"></p><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('monitoring.index.table.healthy') }}</p></div>
                            <div><p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="summaryReady ? attentionCount : '—'"></p><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('monitoring.index.table.attention') }}</p></div>
                        </div>
                    </section>

                    <a href="{{ route('incidents.analytics') }}" class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-700">
                        <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('monitoring.index.workspace.open_incidents') }}</span><span class="rounded-full bg-red-50 px-2.5 py-1 text-sm font-bold text-red-700 dark:bg-red-950/40 dark:text-red-300">{{ $openIncidentCount }}</span></div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.description') }}</p>
                        <span class="mt-4 inline-flex items-center text-sm font-semibold text-purple-700 dark:text-purple-300">{{ __('monitoring.index.workspace.view') }} <span class="ml-1" aria-hidden="true">→</span></span>
                    </a>

                    <a href="{{ route('status-pages.index') }}" class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-700">
                        <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('monitoring.index.workspace.status_pages') }}</span><span class="rounded-full bg-purple-50 px-2.5 py-1 text-sm font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">{{ $statusPageCount }}</span></div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.overview.status_pages') }}</p>
                        <span class="mt-4 inline-flex items-center text-sm font-semibold text-purple-700 dark:text-purple-300">{{ __('monitoring.index.workspace.manage') }} <span class="ml-1" aria-hidden="true">→</span></span>
                    </a>
                </aside>
            </div>

            <div x-show="monitoringIds.length === 0" x-cloak>
                <x-container class="text-center">
                    <x-heading type="h2">{{ __('monitoring.no_monitoring.title') }}</x-heading>
                    <x-paragraph space="true">{{ __('monitoring.no_monitoring.text') }}</x-paragraph>
                    @if ($canCreateMonitoring)
                        <x-primary-button :href="route('monitorings.create')" data-form-modal-trigger data-form-modal-name="monitoring-form-modal">{{ __('button.create') }}</x-primary-button>
                    @endif
                </x-container>
            </div>

            <x-form-modal name="monitoring-form-modal" title="{{ __('monitoring.title') }}"
                description="{{ __('monitoring.form.sections.basic') }}" max-width="6xl"
                :show="in_array($modalForm, ['monitoring-create', 'monitoring-edit'], true)">
                <div class="p-6" x-ref="content">
                    @if ($modalForm === 'monitoring-create')
                        @include('monitorings._modal-form', array_merge($modalFormData, [
                            'action' => route('monitorings.store'),
                            'modal' => true,
                        ]))
                    @elseif ($modalForm === 'monitoring-edit' && $modalMonitoring)
                        @include('monitorings._modal-form', array_merge($modalFormData, [
                            'action' => route('monitorings.update', $modalMonitoring),
                            'monitoring' => $modalMonitoring,
                            'modal' => true,
                        ]))
                    @else
                        <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                        <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                        <div x-html="content"></div>
                    @endif
                </div>
            </x-form-modal>
        </div>
    </x-main>

</x-app-layout>
