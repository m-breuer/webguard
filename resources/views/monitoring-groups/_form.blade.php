@csrf
@if (isset($monitoringGroup))
    @method('PATCH')
@endif

<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('monitoring_group.form.name')" />
        <x-text-input id="name" type="text" name="name" :value="old('name', $monitoringGroup->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('monitoring_group.form.description')" />
        <x-textarea id="description" name="description" rows="5">{{ old('description', $monitoringGroup->description ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <x-secondary-button :href="route('monitoring-groups.index')">
            {{ __('button.cancel') }}
        </x-secondary-button>
        <x-primary-button>
            {{ isset($monitoringGroup) ? __('button.update') : __('button.create') }}
        </x-primary-button>
    </div>
</div>
