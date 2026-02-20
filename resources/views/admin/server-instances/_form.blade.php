@csrf

<div>
    <x-input-label for="code" :value="__('admin.server_instances.fields.code')" />
    <x-text-input id="code" type="text" name="code" :value="old('code', $instance->code ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('code')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="api_key" :value="__('admin.server_instances.fields.api_key')" />
    <x-text-input id="api_key" type="text" name="api_key" :value="old('api_key')" :required="!isset($instance)" />
    @if (isset($instance))
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('admin.server_instances.messages.api_key_optional') }}
        </p>
    @endif
    <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
</div>

<div class="mt-4">
    <input type="hidden" name="is_active" value="0">
    <label for="is_active" class="inline-flex items-center">
        <x-checkbox-input id="is_active" name="is_active" type="checkbox" value="1"
            :checked="old('is_active', $instance->is_active ?? true)" />
        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.server_instances.fields.active') }}</span>
    </label>
    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
</div>

<div class="mt-4 flex items-center justify-end">
    <x-primary-button class="ms-4">
        {{ isset($instance) ? __('button.update') : __('button.create') }}
    </x-primary-button>
</div>
