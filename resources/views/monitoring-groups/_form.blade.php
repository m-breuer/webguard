@csrf
@if (isset($monitoringGroup))
    @method('PATCH')
@endif

@php
    $selectedMonitoringIds = old(
        'monitoring_ids',
        isset($monitoringGroup) ? $monitoringGroup->monitorings->pluck('id')->all() : []
    );
    $selectedMonitoringIds = is_array($selectedMonitoringIds) ? $selectedMonitoringIds : [];
    $monitoringOptions = collect($monitorings ?? [])->map(static fn ($monitoring): array => [
        'value' => (string) $monitoring->id,
        'label' => $monitoring->name . ' — ' . $monitoring->target,
    ])->values();
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('monitoring_group.form.name')" />
        <x-text-input
            id="name"
            type="text"
            name="name"
            :value="old('name', $monitoringGroup->name ?? '')"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('monitoring_group.form.description')" />
        <x-textarea
            id="description"
            name="description"
            rows="5"
        >{{ old('description', $monitoringGroup->description ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>

    <div>
        <x-input-label for="monitoring_ids" :value="__('monitoring_group.form.monitorings')" />
        <x-multi-select
            id="monitoring_ids"
            name="monitoring_ids"
            :options="$monitoringOptions"
            :selected="$selectedMonitoringIds"
            :placeholder="__('monitoring_group.form.no_monitorings')"
            :search-placeholder="__('monitoring_group.form.search_monitorings')"
            :select-all-label="__('monitoring_group.form.select_all_monitorings')"
            :all-selected-label="__('monitoring_group.form.all_monitorings_selected')"
            :no-options-label="__('monitoring_group.form.no_monitorings_available')"
            :no-results-label="__('monitoring_group.form.no_monitorings_found')"
            :remove-label="__('monitoring_group.form.remove_monitoring')"
            :clear-label="__('monitoring_group.form.clear_monitorings')"
        />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('monitoring_group.form.monitorings_help') }}</p>
        <x-input-error :messages="$errors->get('monitoring_ids')" />
        <x-input-error :messages="$errors->get('monitoring_ids.*')" />
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <x-secondary-button
            :href="isset($modal) && $modal ? null : route('monitoring-groups.index')"
            type="button"
            x-on:click="$dispatch('close-form-modal', 'monitoring-group-form-modal')"
        >
            {{ __('button.cancel') }}
        </x-secondary-button>
        <x-primary-button> {{ isset($monitoringGroup) ? __('button.update') : __('button.create') }} </x-primary-button>
    </div>
</div>
