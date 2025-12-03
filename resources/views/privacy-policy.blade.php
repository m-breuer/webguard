<x-guest-layout>
    <x-main>
        <x-slot name="logo">
            <a href="/">
                <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
            </a>
        </x-slot>

        <x-heading type="h1">{{ __('legal.privacy_policy.title') }}</x-heading>
        <x-paragraph>{{ __('legal.privacy_policy.content') }}</x-paragraph>
        <!-- Add your privacy policy content here -->
        </div>
    </x-main>
</x-guest-layout>
