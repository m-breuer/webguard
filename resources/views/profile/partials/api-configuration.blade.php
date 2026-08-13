<x-heading type="h2"> {{ __('api.configuration.heading') }} </x-heading>

<x-paragraph>{{ __('api.configuration.description') }}</x-paragraph>

@if (session('api_key_plaintext'))
    <x-api-token-display :token="session('api_key_plaintext')" />
@endif

<form id="api-keys" method="POST" action="{{ route('profile.api-keys.store') }}" class="mt-5 space-y-4">
    @csrf
    <div>
        <x-input-label for="api-key-name" :value="__('api.configuration.fields.name')" />
        <x-text-input
            id="api-key-name"
            name="name"
            class="mt-1 block w-full"
            :value="old('name')"
            required
            maxlength="100"
        />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <fieldset>
        <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('api.configuration.fields.abilities') }}
        </legend>
        <div class="mt-2 space-y-2">
            @foreach (\App\Enums\ApiKeyAbility::cases() as $ability)
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        name="abilities[]"
                        value="{{ $ability->value }}"
                        @checked(in_array($ability->value, old('abilities', []), true))
                    />
                    <span>{{ __('api.configuration.abilities.' . str_replace([':', '-'], ['_', '_'], $ability->value)) }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('abilities')" />
    </fieldset>

    <x-primary-button>{{ __('api.configuration.actions.create_key') }}</x-primary-button>
</form>

@if ($apiKeys?->isNotEmpty())
    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2">{{ __('api.configuration.fields.name') }}</th>
                    <th class="px-3 py-2">{{ __('api.configuration.fields.abilities') }}</th>
                    <th class="px-3 py-2">{{ __('api.configuration.fields.last_used_at') }}</th>
                    <th class="px-3 py-2">{{ __('api.configuration.fields.status') }}</th>
                    <th class="px-3 py-2">
                        <span class="sr-only">{{ __('api.configuration.actions.revoke_key') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($apiKeys as $apiKey)
                    <tr class="border-b border-gray-100 dark:border-gray-700/70">
                        <td class="px-3 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ \App\Services\ApiKeyService::displayName($apiKey) }}
                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ implode(', ', $apiKey->abilities ?? []) }}
                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ $apiKey->last_used_at?->toIso8601String() ?? __('api.configuration.never_used') }}
                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ $apiKey->revoked_at ? __('api.configuration.revoked') : __('api.configuration.active') }}
                        </td>
                        <td class="px-3 py-3 text-right">
                            @if (! $apiKey->revoked_at)
                                <form
                                    method="POST"
                                    action="{{ route('profile.api-keys.destroy', $apiKey) }}"
                                    data-confirm-message="{{ __('api.configuration.messages.confirm_revoke_key') }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button
                                        :icon-only="true"
                                        title="{{ __('api.configuration.actions.revoke_key') }}"
                                        aria-label="{{ __('api.configuration.actions.revoke_key') }}"
                                    >
                                        <x-icon name="x" class="h-4 w-4" />
                                    </x-danger-button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
