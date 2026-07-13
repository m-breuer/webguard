<x-container class="mb-4">
    <div class="space-y-4">
        <div>
            <x-heading type="h2">{{ __('team.ownership.select_label') }}</x-heading>
            <x-paragraph class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ $monitoring->team ? __('team.ownership.team') . ': ' . $monitoring->team->name : __('team.ownership.private') }}
            </x-paragraph>
        </div>

        @if (! $monitoring->isTeamOwned() && $adminTeams->isNotEmpty())
            <form method="POST" action="{{ route('monitorings.team-ownership.store', $monitoring) }}"
                class="flex flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-input-label for="move-team-{{ $monitoring->id }}" :value="__('team.ownership.move_to_team')" />
                    <x-select-input id="move-team-{{ $monitoring->id }}" name="team_id" class="mt-1 block w-full">
                        @foreach ($adminTeams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <x-secondary-button type="submit">{{ __('team.ownership.move_to_team') }}</x-secondary-button>
            </form>
        @endif

        @if ($monitoring->isTeamOwned())
            <form method="POST" action="{{ route('monitorings.team-ownership.destroy', $monitoring) }}">
                @csrf
                @method('DELETE')
                <x-secondary-button type="submit">{{ __('team.ownership.move_to_private') }}</x-secondary-button>
            </form>
        @endif
    </div>
</x-container>
