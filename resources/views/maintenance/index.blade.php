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

                    <form method="POST" action="{{ route('maintenance.store') }}" class="mt-6 space-y-4" x-data="{ scope: @js(old('scope', 'monitoring')) }">
                        @csrf

                        <div class="grid gap-4 lg:grid-cols-4">
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

                            <div>
                                <x-input-label for="maintenance_from" :value="__('maintenance.form.from')" />
                                <x-text-input id="maintenance_from" type="datetime-local" name="maintenance_from" :value="old('maintenance_from')" required />
                                <x-input-error :messages="$errors->get('maintenance_from')" />
                            </div>

                            <div>
                                <x-input-label for="maintenance_until" :value="__('maintenance.form.until')" />
                                <x-text-input id="maintenance_until" type="datetime-local" name="maintenance_until" :value="old('maintenance_until')" />
                                <x-input-error :messages="$errors->get('maintenance_until')" />
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

                <div class="mt-6 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                    <div class="max-h-[36rem] overflow-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                            <thead class="sticky top-0 z-10 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="px-4 py-3">{{ __('monitoring.index.table.name') }}</th>
                                    <th scope="col" class="px-4 py-3">{{ __('monitoring.index.table.status') }}</th>
                                    <th scope="col" class="px-4 py-3">{{ __('maintenance.form.from') }}</th>
                                    <th scope="col" class="px-4 py-3">{{ __('maintenance.form.until') }}</th>
                                    <th scope="col" class="px-4 py-3">{{ __('maintenance.table.groups') }}</th>
                                    @if ($canManageMaintenance)
                                        <th scope="col" class="px-4 py-3">{{ __('maintenance.table.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($monitorings as $monitoring)
                                    <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $monitoring->name }}</div>
                                            <div class="mt-1 max-w-md truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $monitoring->target }}">{{ $monitoring->target }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($monitoring->isUnderMaintenance())
                                                <x-badge type="info">{{ __('maintenance.status.active') }}</x-badge>
                                            @elseif ($monitoring->maintenance_from && $monitoring->maintenance_from->isFuture())
                                                <x-badge type="warning">{{ __('maintenance.status.upcoming') }}</x-badge>
                                            @elseif ($monitoring->maintenance_from)
                                                <x-badge type="neutral">{{ __('maintenance.status.expired') }}</x-badge>
                                            @else
                                                <x-badge type="success">{{ __('maintenance.status.none') }}</x-badge>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">
                                            {{ $monitoring->maintenance_from?->format('Y-m-d H:i') ?? '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">
                                            {{ $monitoring->maintenance_until?->format('Y-m-d H:i') ?? ($monitoring->maintenance_from ? __('maintenance.status.open_ended') : '-') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($monitoring->groups->isNotEmpty())
                                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                                    @foreach ($monitoring->groups as $group)
                                                        <x-badge type="neutral">{{ $group->name }}</x-badge>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">-</span>
                                            @endif
                                        </td>
                                        @if ($canManageMaintenance)
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if ($monitoring->maintenance_from && in_array($monitoring->id, $manageableMonitoringIds, true))
                                                    <form method="POST" action="{{ route('maintenance.destroy') }}" class="inline-flex">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="monitoring_id" value="{{ $monitoring->id }}">
                                                        <button
                                                            type="submit"
                                                            class="text-sm font-medium text-purple-600 hover:underline dark:text-purple-300"
                                                            x-data
                                                            x-on:click.prevent="if (confirm('{{ __('maintenance.actions.clear_confirmation') }}')) $el.closest('form').submit()">
                                                            {{ __('maintenance.actions.clear') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageMaintenance ? 6 : 5 }}" class="px-4 py-10 text-center">
                                            <x-heading type="h3">{{ __('maintenance.empty.title') }}</x-heading>
                                            <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('maintenance.empty.text') }}
                                            </x-paragraph>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-container>
        </div>
    </x-main>
</x-app-layout>
