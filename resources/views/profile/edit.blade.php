<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('profile.title') }}
        </x-heading>
    </x-slot>

    <x-main>
        @include('profile.partials.update-profile-information-form')

        @include('profile.partials.update-password-form')

        @include('profile.partials.api-configuration')

        @include('profile.partials.api-docs')

        @include('profile.partials.delete-user-form')
    </x-main>
</x-app-layout>
