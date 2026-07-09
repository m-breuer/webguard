<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('maintenance.title') }}</x-heading>
    </x-slot>

    <x-main>
        <div @class([
            'grid gap-6',
            'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]' => $canManageMaintenance,
        ])>
            @if ($canManageMaintenance)
                <x-container>
                    <x-heading type="h2">{{ __('maintenance.schedule.heading') }}</x-heading>
                    <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('maintenance.schedule.description') }}
                    </x-paragraph>

                    <form method="POST" action="{{ route('maintenance.store') }}" class="mt-6 space-y-4" x-data="{ scope: @js(old('scope', 'monitoring')) }">
                        @csrf

                        <div>
                            <x-input-label for="scope" :value="__('maintenance.form.scope')" />
                            <x-select-input id="scope" class="mt-1 block w-full" name="scope" x-model="scope">
                                <option value="monitoring">{{ __('maintenance.form.scopes.monitoring') }}</option>
                                <option value="group">{{ __('maintenance.form.scopes.group') }}</option>
                            </x-select-input>
                            <x-input-error :messages="$errors->get('scope')" />
                        </div>

                        <div>
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
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
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

                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('maintenance.form.help') }}
                        </p>

                        <x-primary-button>{{ __('maintenance.actions.schedule') }}</x-primary-button>
                    </form>
                </x-container>
            @endif

            <x-container>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <x-heading type="h2">{{ __('maintenance.windows.heading') }}</x-heading>
                        <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('maintenance.windows.description') }}
                        </x-paragraph>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($monitorings as $monitoring)
                        <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $monitoring->name }}</div>
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $monitoring->target }}</div>
                                    @if ($monitoring->groups->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($monitoring->groups as $group)
                                                <x-badge type="neutral">{{ $group->name }}</x-badge>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if ($monitoring->isUnderMaintenance())
                                    <x-badge type="info">{{ __('maintenance.status.active') }}</x-badge>
                                @elseif ($monitoring->maintenance_from && $monitoring->maintenance_from->isFuture())
                                    <x-badge type="warning">{{ __('maintenance.status.upcoming') }}</x-badge>
                                @elseif ($monitoring->maintenance_from)
                                    <x-badge type="neutral">{{ __('maintenance.status.expired') }}</x-badge>
                                @else
                                    <x-badge type="success">{{ __('maintenance.status.none') }}</x-badge>
                                @endif
                            </div>

                            @if ($monitoring->maintenance_from)
                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">{{ __('maintenance.form.from') }}</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $monitoring->maintenance_from->toDayDateTimeString() }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">{{ __('maintenance.form.until') }}</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $monitoring->maintenance_until?->toDayDateTimeString() ?? __('maintenance.status.open_ended') }}</dd>
                                    </div>
                                </dl>

                                @if ($canManageMaintenance && in_array($monitoring->id, $manageableMonitoringIds, true))
                                    <form method="POST" action="{{ route('maintenance.destroy') }}" class="mt-4">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="monitoring_id" value="{{ $monitoring->id }}">
                                        <x-secondary-button
                                            x-data
                                            x-on:click.prevent="if (confirm('{{ __('maintenance.actions.clear_confirmation') }}')) $el.closest('form').submit()">
                                            {{ __('maintenance.actions.clear') }}
                                        </x-secondary-button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @empty
                        <div class="rounded-md border border-gray-200 p-6 text-center dark:border-gray-700">
                            <x-heading type="h3">{{ __('maintenance.empty.title') }}</x-heading>
                            <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('maintenance.empty.text') }}
                            </x-paragraph>
                        </div>
                    @endforelse
                </div>
            </x-container>
        </div>
    </x-main>
</x-app-layout>
