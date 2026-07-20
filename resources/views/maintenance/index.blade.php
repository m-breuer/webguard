<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('maintenance.title') }}</x-heading>
    </x-slot>

    <x-main>
        <div class="space-y-6">
            @if ($canManageMaintenance)
                <x-container>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <x-heading type="h2">{{ __('maintenance.schedule.heading') }}</x-heading>
                            <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('maintenance.schedule.description') }}
                            </x-paragraph>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('maintenance.store') }}" class="mt-6 space-y-4" x-data="{ scope: @js(old('scope', 'monitoring')), mode: @js(old('mode', 'one_off')) }">
                        @csrf

                        <div class="grid gap-4 lg:grid-cols-5">
                            <div>
                                <x-input-label for="mode" :value="__('maintenance.form.mode')" />
                                <x-select-input id="mode" class="mt-1 block w-full" name="mode" x-model="mode">
                                    <option value="one_off">{{ __('maintenance.form.modes.one_off') }}</option>
                                    <option value="recurring">{{ __('maintenance.form.modes.recurring') }}</option>
                                </x-select-input>
                                <x-input-error :messages="$errors->get('mode')" />
                            </div>

                            <div>
                                <x-input-label for="scope" :value="__('maintenance.form.scope')" />
                                <x-select-input id="scope" class="mt-1 block w-full" name="scope" x-model="scope">
                                    <option value="monitoring">{{ __('maintenance.form.scopes.monitoring') }}</option>
                                    <option value="group">{{ __('maintenance.form.scopes.group') }}</option>
                                </x-select-input>
                                <x-input-error :messages="$errors->get('scope')" />
                            </div>

                            <div x-show="scope === 'monitoring'">
                                <x-input-label for="monitoring_id" :value="__('maintenance.form.monitoring')" />
                                <x-select-input id="monitoring_id" class="mt-1 block w-full" name="monitoring_id">
                                    <option value="">{{ __('maintenance.form.select_monitoring') }}</option>
                                    @foreach ($manageableMonitorings as $monitoring)
                                        <option value="{{ $monitoring->id }}" @selected(old('monitoring_id') === $monitoring->id)>
                                            {{ $monitoring->name }}
                                        </option>
                                    @endforeach
                                </x-select-input>
                                <x-input-error :messages="$errors->get('monitoring_id')" />
                            </div>

                            <div x-show="scope === 'group'">
                                <x-input-label for="monitoring_group_id" :value="__('maintenance.form.group')" />
                                <x-select-input id="monitoring_group_id" class="mt-1 block w-full" name="monitoring_group_id">
                                    <option value="">{{ __('maintenance.form.select_group') }}</option>
                                    @foreach ($monitoringGroups as $monitoringGroup)
                                        <option value="{{ $monitoringGroup->id }}" @selected(old('monitoring_group_id') === $monitoringGroup->id)>
                                            {{ $monitoringGroup->name }} ({{ $monitoringGroup->monitorings_count }})
                                        </option>
                                    @endforeach
                                </x-select-input>
                                <x-input-error :messages="$errors->get('monitoring_group_id')" />
                            </div>

                            <div x-show="mode === 'one_off'">
                                <x-input-label for="maintenance_from" :value="__('maintenance.form.from')" />
                                <x-text-input id="maintenance_from" type="datetime-local" name="maintenance_from" :value="old('maintenance_from')" x-bind:required="mode === 'one_off'" />
                                <x-input-error :messages="$errors->get('maintenance_from')" />
                            </div>

                            <div x-show="mode === 'one_off'">
                                <x-input-label for="maintenance_until" :value="__('maintenance.form.until')" />
                                <x-text-input id="maintenance_until" type="datetime-local" name="maintenance_until" :value="old('maintenance_until')" />
                                <x-input-error :messages="$errors->get('maintenance_until')" />
                            </div>

                            <div x-show="mode === 'recurring'">
                                <x-input-label for="recurring_starts_at" :value="__('maintenance.form.recurring_starts_at')" />
                                <x-text-input id="recurring_starts_at" type="datetime-local" name="recurring_starts_at" :value="old('recurring_starts_at')" x-bind:required="mode === 'recurring'" />
                                <x-input-error :messages="$errors->get('recurring_starts_at')" />
                            </div>

                            <div x-show="mode === 'recurring'">
                                <x-input-label for="recurrence" :value="__('maintenance.form.recurrence')" />
                                <x-select-input id="recurrence" class="mt-1 block w-full" name="recurrence" x-bind:required="mode === 'recurring'">
                                    <option value="weekly" @selected(old('recurrence', 'weekly') === 'weekly')>{{ __('maintenance.form.recurrences.weekly') }}</option>
                                    <option value="monthly" @selected(old('recurrence') === 'monthly')>{{ __('maintenance.form.recurrences.monthly') }}</option>
                                </x-select-input>
                                <x-input-error :messages="$errors->get('recurrence')" />
                            </div>

                            <div x-show="mode === 'recurring'">
                                <x-input-label for="recurring_duration_minutes" :value="__('maintenance.form.duration')" />
                                <x-text-input id="recurring_duration_minutes" type="number" min="1" max="1440" name="recurring_duration_minutes" :value="old('recurring_duration_minutes', 60)" x-bind:required="mode === 'recurring'" />
                                <x-input-error :messages="$errors->get('recurring_duration_minutes')" />
                            </div>

                            <div x-show="mode === 'recurring'">
                                <x-input-label for="recurring_repeat_until" :value="__('maintenance.form.repeat_until')" />
                                <x-text-input id="recurring_repeat_until" type="date" name="recurring_repeat_until" :value="old('recurring_repeat_until')" />
                                <x-input-error :messages="$errors->get('recurring_repeat_until')" />
                            </div>

                            <div x-show="mode === 'recurring'">
                                <x-input-label for="recurring_timezone" :value="__('maintenance.form.timezone')" />
                                <x-text-input id="recurring_timezone" type="text" name="recurring_timezone" :value="old('recurring_timezone', config('app.timezone'))" x-bind:required="mode === 'recurring'" />
                                <x-input-error :messages="$errors->get('recurring_timezone')" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="max-w-3xl text-sm text-gray-600 dark:text-gray-400">
                                {{ __('maintenance.form.help') }}
                            </p>

                            <x-primary-button>{{ __('maintenance.actions.schedule') }}</x-primary-button>
                        </div>
                    </form>
                </x-container>
            @endif

            <x-container>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <x-heading type="h2">{{ __('maintenance.windows.heading') }}</x-heading>
                        <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('maintenance.windows.description') }}
                        </x-paragraph>
                    </div>
                </div>

                <dl class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-md border border-gray-200 px-4 py-3 dark:border-gray-700">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('maintenance.summary.total') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $maintenanceStats['total'] }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-200 px-4 py-3 dark:border-gray-700">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('maintenance.status.active') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $maintenanceStats['active'] }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-200 px-4 py-3 dark:border-gray-700">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('maintenance.status.upcoming') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $maintenanceStats['upcoming'] }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-200 px-4 py-3 dark:border-gray-700">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('maintenance.status.expired') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $maintenanceStats['expired'] }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-200 px-4 py-3 dark:border-gray-700">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('maintenance.status.none') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $maintenanceStats['none'] }}</dd>
                    </div>
                </dl>
            </x-container>

            @if ($recurringWindows->isNotEmpty())
                <x-container>
                    <x-heading type="h2">{{ __('maintenance.recurring.heading') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('maintenance.recurring.description') }}
                    </x-paragraph>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">{{ __('maintenance.recurring.target') }}</th>
                                    <th class="px-3 py-2">{{ __('maintenance.recurring.schedule') }}</th>
                                    <th class="px-3 py-2">{{ __('maintenance.recurring.timezone') }}</th>
                                    <th class="px-3 py-2">{{ __('maintenance.recurring.starts') }}</th>
                                    <th class="px-3 py-2">{{ __('maintenance.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($recurringWindows as $recurringWindow)
                                    <tr>
                                        <td class="px-3 py-3 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $recurringWindow->monitoring?->name ?? $recurringWindow->monitoringGroup?->name }}
                                        </td>
                                        <td class="px-3 py-3 text-gray-600 dark:text-gray-400">
                                            {{ __('maintenance.form.recurrences.' . $recurringWindow->recurrence->value) }}
                                            · {{ $recurringWindow->duration_minutes }} min
                                        </td>
                                        <td class="px-3 py-3 text-gray-600 dark:text-gray-400">{{ $recurringWindow->timezone }}</td>
                                        <td class="px-3 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $recurringWindow->starts_at->setTimezone($recurringWindow->timezone)->toDayDateTimeString() }}
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($recurringWindow->isManageableBy(Auth::user()))
                                                <form method="POST" action="{{ route('maintenance.destroy') }}" class="inline-flex">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="maintenance_window_id" value="{{ $recurringWindow->id }}">
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md text-purple-600 transition hover:bg-purple-50 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-purple-300 dark:hover:bg-purple-950/40"
                                                        title="{{ __('maintenance.actions.clear') }}"
                                                        aria-label="{{ __('maintenance.actions.clear') }}"
                                                        x-data
                                                        x-on:click.prevent="if (confirm('{{ __('maintenance.actions.clear_recurring_confirmation') }}')) $el.closest('form').submit()">
                                                        <x-icon name="x" class="h-4 w-4" />
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-container>
            @endif

            <x-async-table id="maintenance-table" :endpoint="route('maintenance.index')" :paginator="$monitorings"
                :filters="$filters" :initial-filters="$activeFilters" :initial-sort="$sort"
                :initial-direction="$direction"
                search-placeholder="{{ __('search.fields.placeholder', ['attribute' => __('maintenance.title')]) }}">
                <x-slot name="head">
                    <x-table.heading sort="name">{{ __('monitoring.index.table.name') }}</x-table.heading>
                    <x-table.heading sort="maintenance_status">{{ __('monitoring.index.table.status') }}</x-table.heading>
                    <x-table.heading sort="maintenance_from">{{ __('maintenance.form.from') }}</x-table.heading>
                    <x-table.heading sort="maintenance_until">{{ __('maintenance.form.until') }}</x-table.heading>
                    <x-table.heading>{{ __('maintenance.table.groups') }}</x-table.heading>
                    @if ($canManageMaintenance)
                        <x-table.heading>{{ __('maintenance.table.actions') }}</x-table.heading>
                    @endif
                </x-slot>

                <x-slot name="body">
                    @include('maintenance.partials.rows', [
                        'monitorings' => $monitorings,
                        'canManageMaintenance' => $canManageMaintenance,
                        'manageableMonitoringIds' => $manageableMonitoringIds,
                    ])
                </x-slot>
            </x-async-table>
        </div>
    </x-main>
</x-app-layout>
