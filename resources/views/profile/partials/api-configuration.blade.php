<x-container class="mb-4">
    <x-heading type="h2">
        {{ __('api.configuration.heading') }}
    </x-heading>

    <x-paragraph>
        {{ __('api.configuration.description') }}
    </x-paragraph>

    <div class="mt-2 gap-4 md:flex md:items-center">
        <form method="POST" action="{{ route('profile.api-generate-token') }}">
            @csrf
            <x-primary-button>{{ __('api.configuration.actions.generate_token') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('profile.api-revoke-token') }}" class="mt-4 md:mt-0">
            @csrf
            @method('DELETE')
            <x-danger-button>{{ __('api.configuration.actions.revoke_token') }}</x-danger-button>
        </form>
    </div>
    @if (Auth::user()->tokens->isNotEmpty())
        <x-api-token-display :token="Auth::user()->tokens->last()->token" />
    @endif
</x-container>
