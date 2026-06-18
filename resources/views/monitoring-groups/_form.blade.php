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

    <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
        <label for="public_label_enabled" class="flex items-start gap-3">
            <x-checkbox-input id="public_label_enabled" name="public_label_enabled" value="1" :checked="old('public_label_enabled', $monitoringGroup->public_label_enabled ?? false)" />
            <span>
                <span class="block font-medium text-gray-900 dark:text-gray-100">
                    {{ __('monitoring_group.form.public_label_enabled') }}
                </span>
                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">
                    {{ __('monitoring_group.form.public_label_help') }}
                </span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('public_label_enabled')" />
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
