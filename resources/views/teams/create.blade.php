<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('team.create.title') }}</x-heading>
    </x-slot>

    <x-main>
        <x-container>
            <form method="POST" action="{{ route('teams.store') }}">
                @include('teams._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
