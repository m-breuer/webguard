<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('team.edit.title', ['team' => $team->name]) }}</x-heading>
    </x-slot>

    <x-main>
        <x-container>
            <form method="POST" action="{{ route('teams.update', $team) }}">
                @include('teams._form', ['team' => $team])
            </form>
        </x-container>
    </x-main>
</x-app-layout>
