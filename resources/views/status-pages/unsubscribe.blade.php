<x-public-layout>
    <x-slot name="head">
        <meta name="robots" content="noindex">
        <title>{{ __('status_page.public.subscribe.unsubscribe_title', ['statusPageName' => $statusPage->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <div>
            <x-heading>
                {{ __('status_page.public.subscribe.unsubscribe_heading') }}
            </x-heading>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-300">
                {{ $statusPage->name }}
            </p>
        </div>
    </x-slot>

    <x-main>
        <x-container>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('status_page.public.subscribe.unsubscribe_description', ['email' => $subscription->email]) }}
            </p>

            <form method="POST" action="{{ route('public-status-pages.subscribers.destroy', ['statusPage' => $statusPage, 'token' => $token]) }}"
                class="mt-6 space-y-4"
                data-confirm-message="{{ __('status_page.public.subscribe.unsubscribe_confirmation') }}">
                @csrf
                @method('DELETE')

                <input type="hidden" name="email" value="{{ $subscription->email }}">

                <x-primary-button>
                    {{ __('status_page.public.subscribe.unsubscribe_button') }}
                </x-primary-button>
            </form>
        </x-container>
    </x-main>
</x-public-layout>
