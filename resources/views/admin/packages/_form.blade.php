@csrf

<div>
    <x-input-label for="monitoring_limit" :value="__('admin.packages.fields.monitoring_limit')" />
    <x-text-input id="monitoring_limit" type="number" name="monitoring_limit" :value="old('monitoring_limit', $package->monitoring_limit ?? '')" required
        autofocus />
    <x-input-error :messages="$errors->get('monitoring_limit')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="price" :value="__('admin.packages.fields.price')" />
    <x-text-input id="price" type="number" step="0.01" name="price" :value="old('price', $package->price ?? '')"
        required />
    <x-input-error :messages="$errors->get('price')" class="mt-2" />
</div>

<div class="mt-4">
    <input type="hidden" name="is_selectable" value="0">
    <label for="is_selectable" class="inline-flex items-center">
        <x-checkbox-input id="is_selectable" name="is_selectable" type="checkbox" value="1"
            :checked="old('is_selectable', $package->is_selectable ?? true)" />
        <span
            class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.packages.fields.is_selectable') }}</span>
    </label>
    <x-input-error :messages="$errors->get('is_selectable')" class="mt-2" />
</div>

<div class="mt-4 flex items-center justify-end">
    <x-primary-button class="ms-4">
        {{ isset($package) ? __('button.update') : __('button.create') }}
    </x-primary-button>
</div>
