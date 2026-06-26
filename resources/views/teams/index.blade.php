<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('team.title') }}</x-heading>

        @if (!Auth::user()->isDemo())
            <x-primary-button :href="route('teams.create')" class="sm:ml-auto">
                {{ __('team.actions.create') }}
            </x-primary-button>
        @endif
    </x-slot>

    <x-main>
        <x-container>
            @if ($teams->count() === 0)
                <x-paragraph>{{ __('team.empty.teams') }}</x-paragraph>
            @else
                <div class="space-y-3">
                    @foreach ($teams as $team)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 py-3 last:border-b-0 dark:border-gray-700">
                            <div>
                                <x-heading type="h2">{{ $team->name }}</x-heading>
                                @if ($team->description)
                                    <x-paragraph class="text-sm text-gray-600 dark:text-gray-400">{{ $team->description }}</x-paragraph>
                                @endif
                                <x-paragraph class="mt-1 text-sm text-gray-500">
                                    {{ __('team.fields.members') }}: {{ $team->memberships_count }}
                                    · {{ __('team.fields.monitorings') }}: {{ $team->monitorings_count }}
                                </x-paragraph>
                            </div>

                            <x-secondary-button :href="route('teams.show', $team)">
                                {{ __('button.show') }}
                            </x-secondary-button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{ $teams->links() }}
        </x-container>
    </x-main>
</x-app-layout>
