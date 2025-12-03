<x-container class="mb-4">
    <x-heading type="h2">{{ __('api.docs.heading') }}</x-heading>
    <x-paragraph>
        {{ __('api.docs.description') }}
    </x-paragraph>
    @if (env('APP_ENV') === 'production')
        <x-paragraph>
            <a href="{{ route('scribe') }}" target="_blank"
                class="text-purple-800 underline dark:text-purple-400">{{ __('api.docs.link') }}</a>
        </x-paragraph>
    @endif
</x-container>
