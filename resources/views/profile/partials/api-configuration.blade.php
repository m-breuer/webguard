<x-heading type="h2">
        {{ __('api.configuration.heading') }}
    </x-heading>

    <x-paragraph>
        {{ __('api.configuration.description') }}
    </x-paragraph>

    <div class="flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('profile.api-generate-token') }}">
            @csrf
            <x-primary-button>{{ __('api.configuration.actions.generate_token') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('profile.api-revoke-token') }}"
            data-confirm-message="{{ __('api.configuration.messages.confirm_revoke_token') }}">
            @csrf
            @method('DELETE')
            <x-danger-button :icon-only="true" title="{{ __('api.configuration.actions.revoke_token') }}"
                aria-label="{{ __('api.configuration.actions.revoke_token') }}">
                <x-icon name="x" class="h-4 w-4" />
            </x-danger-button>
        </form>
    </div>
    @if (Auth::user()->tokens->isNotEmpty())
        <x-api-token-display :token="Auth::user()->tokens->last()->token" />
    @endif
