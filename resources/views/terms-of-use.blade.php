<x-guest-layout>
    <x-main>
        <x-slot name="logo">
            <img src="{{ Vite::asset('resources/images/Logo-WebGuard.png') }}" alt="Logo" class="me-2 h-12 w-12">
        </x-slot>

        <x-heading type="h1">{{ __('legal.terms_of_use.title') }}</x-heading>
        <x-paragraph>{{ __('legal.terms_of_use.content') }}</x-paragraph>
        <!-- Add your terms of use content here -->
    </x-main>
</x-guest-layout>
