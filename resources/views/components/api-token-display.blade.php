<div x-data="{ copied: false }" class="mt-4" id="api-token">
    <x-input-label for="api_key_field" :value="__('api.configuration.fields.token')" />
    <div class="mt-2 flex items-center gap-2">
        <x-text-input type="text" id="api_key_field" class="block w-full" value="{{ $token }}" readonly />

        <x-primary-button
            @click="
            const apiKeyInput = document.getElementById('api_key_field');
            apiKeyInput.select();
            apiKeyInput.setSelectionRange(0, 99999); /* For mobile devices */
            document.execCommand('copy');
            copied = true;
            setTimeout(() => copied = false, 2000);
        ">
            {{ __('api.configuration.actions.copy') }}
        </x-primary-button>
        <span x-show="copied" x-transition.opacity
            class="text-sm text-green-600 dark:text-green-400">{{ __('api.configuration.messages.copied') }}</span>
    </div>
    <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        {{ __('api.configuration.messages.api_key_confidential_warning') }}
    </x-paragraph>
</div>
