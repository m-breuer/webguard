<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('auth.verify_email.heading') }}
        </x-heading>
    </x-slot>

    <x-main>
        <x-container>
            <x-heading type="h2" class="mb-2">{{ __('auth.verify_email.subheading') }}</x-heading>
            <x-paragraph class="mb-4">
                {{ __('auth.verify_email.description') }}
            </x-paragraph>

            @if (session('status') == 'verification-link-sent')
                <x-paragraph class="mb-4 font-medium text-green-600 dark:text-green-400">
                    {{ __('auth.verify_email.link_sent') }}
                </x-paragraph>
            @endif

            <div class="mt-4 flex items-center justify-center">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-primary-button>
                        {{ __('auth.verify_email.resend_button') }}
                    </x-primary-button>
                </form>
            </div>
        </x-container>
    </x-main>
</x-app-layout>