<x-public-layout>
    <x-slot name="head">
        <title>
            {{ __('monitoring.public_label.subscribe.unsubscribe_title', ['monitoringName' => $monitoring->name]) }}
        </title>
    </x-slot>

    <x-slot name="header">
        <div>
            <x-heading> {{ __('monitoring.public_label.subscribe.unsubscribe_heading') }} </x-heading>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-300">{{ $monitoring->name }}</p>
        </div>
    </x-slot>

    <x-main>
        <x-container>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('monitoring.public_label.subscribe.unsubscribe_description', ['email' => $subscriber->email]) }}
            </p>

            <form
                method="POST"
                action="{{ route('public-label.subscribers.destroy', ['monitoring' => $monitoring, 'token' => $token]) }}"
                class="mt-6 space-y-4"
                data-confirm-message="{{ __('monitoring.public_label.subscribe.unsubscribe_confirmation') }}"
            >
                @csrf
                @method('DELETE')

                <input type="hidden" name="email" value="{{ $subscriber->email }}" />

                <x-primary-button> {{ __('monitoring.public_label.subscribe.unsubscribe_button') }} </x-primary-button>
            </form>
        </x-container>
    </x-main>
</x-public-layout>
