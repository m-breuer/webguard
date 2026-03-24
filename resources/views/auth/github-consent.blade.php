<x-guest-layout card-width="sm:max-w-2xl">
    <div class="my-6 text-center">
        <x-heading type="h1">
            {{ __('auth.github_consent.title') }}
        </x-heading>
        <x-paragraph class="mt-2">
            {{ __('auth.github_consent.description') }}
        </x-paragraph>
    </div>

    <form method="POST" action="{{ route('github.consent.store') }}" class="space-y-4">
        @csrf

        <label for="github_terms" class="inline-flex items-start">
            <input id="github_terms" name="terms" type="checkbox" value="1"
                class="mt-0.5 rounded-sm border-gray-300 text-purple-600 shadow-xs focus:border-purple-300 focus:ring-3 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
                @checked(old('terms')) required>
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">
                {!! __('auth.register.terms_agreement', ['terms_link' => route('terms-of-use'), 'privacy_link' => route('gdpr')]) !!}
            </span>
        </label>
        <x-input-error :messages="$errors->get('terms')" />

        <div class="mt-4 flex items-center gap-3">
            <x-primary-button>
                {{ __('auth.github_consent.button') }}
            </x-primary-button>
            <x-secondary-button :href="route('login')">
                {{ __('auth.github_consent.cancel') }}
            </x-secondary-button>
        </div>
    </form>
</x-guest-layout>

