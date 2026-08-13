<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('maintenance.title') }}</x-heading>
    </x-slot>

    <x-main
        x-data="maintenancePage(@js(route('api.maintenance.index')), {
        loading: @js(__('maintenance.messages.loading')),
        error: @js(__('maintenance.messages.error')),
        clearConfirmation: @js(__('maintenance.actions.clear_confirmation')),
        clearRecurringConfirmation: @js(__('maintenance.actions.clear_recurring_confirmation')),
    })"
        x-init="load()"
    >
        <div
            x-show="message"
            x-cloak
            class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-200"
            x-text="message"
        ></div>
        <div
            x-show="error"
            x-cloak
            class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200"
            x-text="error"
        ></div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]">
            <x-container x-show="loading || canManageMaintenance" x-cloak>
                <x-heading type="h2">{{ __('maintenance.schedule.heading') }}</x-heading>
                <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('maintenance.schedule.description') }}
                </x-paragraph>

                <form
                    method="POST"
                    action="{{ route('maintenance.store') }}"
                    class="mt-6 space-y-4"
                    @submit.prevent="schedule()"
                >
                    @csrf

                    <div>
                        <x-input-label for="mode" :value="__('maintenance.form.mode')" />
                        <x-select-input
                            id="mode"
                            class="mt-1 block w-full"
                            name="mode"
                            x-model="mode"
                            x-bind:disabled="loading || submitting"
                        >
                            <option value="one_off">{{ __('maintenance.form.modes.one_off') }}</option>
                            <option value="recurring">{{ __('maintenance.form.modes.recurring') }}</option>
                        </x-select-input>
                    </div>

                    <div>
                        <x-input-label for="scope" :value="__('maintenance.form.scope')" />
                        <x-select-input
                            id="scope"
                            class="mt-1 block w-full"
                            name="scope"
                            x-model="scope"
                            x-bind:disabled="loading || submitting"
                        >
                            <option value="monitoring">{{ __('maintenance.form.scopes.monitoring') }}</option>
                            <option value="group">{{ __('maintenance.form.scopes.group') }}</option>
                        </x-select-input>
                    </div>

                    <div>
                        <div x-show="scope === 'monitoring'">
                            <x-input-label for="monitoring_id" :value="__('maintenance.form.monitoring')" />
                            <x-select-input
                                id="monitoring_id"
                                class="mt-1 block w-full"
                                name="monitoring_id"
                                x-model="monitoringId"
                                x-bind:disabled="loading || submitting"
                            >
                                <option value="">{{ __('maintenance.form.select_monitoring') }}</option>
                                <template x-for="option in monitoringOptions" :key="option.id">
                                    <option x-bind:value="option.id" x-text="option.name"></option>
                                </template>
                            </x-select-input>
                        </div>

                        <div x-show="scope === 'group'">
                            <x-input-label for="monitoring_group_id" :value="__('maintenance.form.group')" />
                            <x-select-input
                                id="monitoring_group_id"
                                class="mt-1 block w-full"
                                name="monitoring_group_id"
                                x-model="monitoringGroupId"
                                x-bind:disabled="loading || submitting"
                            >
                                <option value="">{{ __('maintenance.form.select_group') }}</option>
                                <template x-for="group in monitoringGroups" :key="group.id">
                                    <option
                                        x-bind:value="group.id"
                                        x-text="group.name + ' (' + group.monitorings_count + ')'"
                                    ></option>
                                </template>
                            </x-select-input>
                        </div>
                    </div>

                    <div x-show="mode === 'one_off'" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="maintenance_from" :value="__('maintenance.form.from')" />
                            <x-text-input
                                id="maintenance_from"
                                type="datetime-local"
                                name="maintenance_from"
                                x-model="maintenanceFrom"
                                required
                                x-bind:disabled="loading || submitting"
                            />
                        </div>

                        <div>
                            <x-input-label for="maintenance_until" :value="__('maintenance.form.until')" />
                            <x-text-input
                                id="maintenance_until"
                                type="datetime-local"
                                name="maintenance_until"
                                x-model="maintenanceUntil"
                                x-bind:disabled="loading || submitting"
                            />
                        </div>
                    </div>

                    <div x-show="mode === 'recurring'" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label
                                for="recurring_starts_at"
                                :value="__('maintenance.form.recurring_starts_at')"
                            />
                            <x-text-input
                                id="recurring_starts_at"
                                type="datetime-local"
                                name="recurring_starts_at"
                                x-model="recurringStartsAt"
                                x-bind:required="mode === 'recurring'"
                                x-bind:disabled="loading || submitting"
                            />
                        </div>

                        <div>
                            <x-input-label for="recurrence" :value="__('maintenance.form.recurrence')" />
                            <x-select-input
                                id="recurrence"
                                class="mt-1 block w-full"
                                name="recurrence"
                                x-model="recurrence"
                                x-bind:required="mode === 'recurring'"
                                x-bind:disabled="loading || submitting"
                            >
                                <option value="weekly">{{ __('maintenance.form.recurrences.weekly') }}</option>
                                <option value="monthly">{{ __('maintenance.form.recurrences.monthly') }}</option>
                            </x-select-input>
                        </div>

                        <div>
                            <x-input-label for="recurring_duration_minutes" :value="__('maintenance.form.duration')" />
                            <x-text-input
                                id="recurring_duration_minutes"
                                type="number"
                                min="1"
                                max="1440"
                                name="recurring_duration_minutes"
                                x-model="recurringDurationMinutes"
                                x-bind:required="mode === 'recurring'"
                                x-bind:disabled="loading || submitting"
                            />
                        </div>

                        <div>
                            <x-input-label for="recurring_repeat_until" :value="__('maintenance.form.repeat_until')" />
                            <x-text-input
                                id="recurring_repeat_until"
                                type="date"
                                name="recurring_repeat_until"
                                x-model="recurringRepeatUntil"
                                x-bind:disabled="loading || submitting"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="recurring_timezone" :value="__('maintenance.form.timezone')" />
                            <x-text-input
                                id="recurring_timezone"
                                type="text"
                                name="recurring_timezone"
                                x-model="recurringTimezone"
                                x-bind:required="mode === 'recurring'"
                                x-bind:disabled="loading || submitting"
                            />
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('maintenance.form.help') }}</p>

                    <x-primary-button x-bind:disabled="loading || submitting">
                        <span x-text="submitting ? '{{ __('maintenance.messages.loading') }}' : '{{ __('maintenance.actions.schedule') }}'"></span>
                    </x-primary-button>
                </form>
            </x-container>

            <x-container>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <x-heading type="h2">{{ __('maintenance.windows.heading') }}</x-heading>
                        <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('maintenance.windows.description') }}
                        </x-paragraph>
                    </div>
                </div>

                <div x-show="loading" x-cloak class="mt-6">
                    <x-loading-indicator>{{ __('maintenance.messages.loading') }}</x-loading-indicator>
                </div>

                <dl x-show="! loading" x-cloak class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <template
                        x-for="stat in [
                        ['{{ __('maintenance.summary.total') }}', stats.total],
                        ['{{ __('maintenance.status.active') }}', stats.active],
                        ['{{ __('maintenance.status.upcoming') }}', stats.upcoming],
                        ['{{ __('maintenance.status.expired') }}', stats.expired],
                        ['{{ __('maintenance.status.none') }}', stats.none]
                    ]"
                        :key="stat[0]"
                    >
                        <div class="rounded-md border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <dt
                                class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                x-text="stat[0]"
                            ></dt>
                            <dd
                                class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100"
                                x-text="stat[1]"
                            ></dd>
                        </div>
                    </template>
                </dl>

                <div x-show="! loading && recurringWindows.length > 0" x-cloak class="mt-6">
                    <x-heading type="h3">{{ __('maintenance.recurring.heading') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('maintenance.recurring.description') }}
                    </x-paragraph>
                    <div class="mt-4 space-y-3">
                        <template x-for="window in recurringWindows" :key="window.id">
                            <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                            x-text="window.target"
                                        ></div>
                                        <div
                                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                            x-text="window.recurrence === 'weekly' ? '{{ __('maintenance.form.recurrences.weekly') }}' : '{{ __('maintenance.form.recurrences.monthly') }}'"
                                        ></div>
                                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            <time
                                                x-bind:datetime="window.starts_at"
                                                x-bind:title="formatDateTime(window.starts_at, window.timezone)"
                                                x-text="formatDateTime(window.starts_at, window.timezone)"
                                            ></time>
                                            <span aria-hidden="true"> · </span>
                                            <span x-text="formatDuration(window.duration_minutes)"></span>
                                            <span aria-hidden="true"> · </span>
                                            <span x-text="window.timezone"></span>
                                        </div>
                                    </div>
                                    <button
                                        x-show="window.can_manage"
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                        x-bind:disabled="submitting"
                                        @click="clearRecurringWindow(window.id)"
                                        title="{{ __('maintenance.actions.clear') }}"
                                        aria-label="{{ __('maintenance.actions.clear') }}"
                                    >
                                        <x-icon name="x" class="mr-2 h-4 w-4" />
                                        {{ __('maintenance.actions.clear') }}
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div
                    x-show="! loading && windows.length === 0"
                    x-cloak
                    class="mt-6 rounded-md border border-gray-200 p-6 text-center dark:border-gray-700"
                >
                    <x-heading type="h3">{{ __('maintenance.empty.title') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('maintenance.empty.text') }}
                    </x-paragraph>
                </div>

                <div x-show="! loading && windows.length > 0" x-cloak class="mt-6 space-y-3">
                    <template x-for="monitoring in windows" :key="monitoring.id">
                        <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div
                                        class="font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="monitoring.name"
                                    ></div>
                                    <div
                                        class="mt-1 text-sm break-all text-gray-500 dark:text-gray-400"
                                        x-text="monitoring.target"
                                    ></div>
                                    <div x-show="monitoring.groups.length > 0" class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="group in monitoring.groups" :key="group.id">
                                            <span
                                                class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                                x-text="group.name"
                                            ></span>
                                        </template>
                                    </div>
                                </div>

                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    x-bind:class="statusClasses(monitoring.status)"
                                    x-text="monitoring.status === 'active' ? '{{ __('maintenance.status.active') }}' : (monitoring.status === 'upcoming' ? '{{ __('maintenance.status.upcoming') }}' : (monitoring.status === 'expired' ? '{{ __('maintenance.status.expired') }}' : '{{ __('maintenance.status.none') }}'))"
                                ></span>
                            </div>

                            <div x-show="monitoring.maintenance_from" class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('maintenance.form.from') }}</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">
                                        <time
                                            x-bind:datetime="monitoring.maintenance_from"
                                            x-bind:title="formatDateTime(monitoring.maintenance_from)"
                                            x-text="formatDateTime(monitoring.maintenance_from)"
                                        ></time>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('maintenance.form.until') }}</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">
                                        <time
                                            x-show="monitoring.maintenance_until"
                                            x-bind:datetime="monitoring.maintenance_until"
                                            x-bind:title="formatDateTime(monitoring.maintenance_until)"
                                            x-text="formatDateTime(monitoring.maintenance_until)"
                                        ></time>
                                        <span x-show="! monitoring.maintenance_until">{{ __('maintenance.status.open_ended') }}</span>
                                    </dd>
                                </div>
                            </div>

                            <button
                                x-show="monitoring.maintenance_from && monitoring.can_manage"
                                type="button"
                                class="mt-4 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                x-bind:disabled="submitting"
                                @click="clearWindow(monitoring.id)"
                            >
                                {{ __('maintenance.actions.clear') }}
                            </button>
                        </div>
                    </template>
                </div>

                <div
                    x-show="! loading && pagination.last_page > 1"
                    x-cloak
                    class="mt-6 flex items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-300"
                >
                    <span>
                        <span x-text="pagination.from ?? 0"></span>–<span x-text="pagination.to ?? 0"></span> /
                        <span x-text="pagination.total"></span>
                    </span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded-md bg-gray-100 px-3 py-2 font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100"
                            title="{{ __('pagination.previous') }}"
                            aria-label="{{ __('pagination.previous') }}"
                            x-bind:disabled="pagination.current_page <= 1 || loading"
                            @click="load(pagination.current_page - 1)"
                        >
                            <x-icon name="arrow-left" class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-gray-100 px-3 py-2 font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100"
                            title="{{ __('pagination.next') }}"
                            aria-label="{{ __('pagination.next') }}"
                            x-bind:disabled="pagination.current_page >= pagination.last_page || loading"
                            @click="load(pagination.current_page + 1)"
                        >
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </x-container>
        </div>
    </x-main>
</x-app-layout>
