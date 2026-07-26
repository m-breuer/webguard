<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">{{ __('team.title') }}</x-heading>

        @if (!Auth::user()->isDemo())
            <x-primary-button :href="route('teams.create')" class="sm:ml-auto"
                data-form-modal-trigger data-form-modal-name="team-form-modal">
                {{ __('team.actions.create') }}
            </x-primary-button>
        @endif
    </x-slot>

    <x-main>
        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}">
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

                            <x-secondary-button :href="route('teams.show', $team)" :icon-only="true"
                                title="{{ __('button.show') }}" aria-label="{{ __('button.show') }}">
                                <x-icon name="eye" class="h-4 w-4" />
                            </x-secondary-button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{ $teams->links() }}
            </x-container>

            <x-form-modal name="team-form-modal" title="{{ __('team.title') }}"
                description="{{ __('team.create.title') }}" max-width="3xl"
                :show="in_array($modalForm, ['team-create', 'team-edit'], true)">
                <div class="p-6" x-ref="content">
                    @if ($modalForm === 'team-create')
                        @include('teams._modal-form', [
                            'action' => route('teams.store'),
                            'modalForm' => 'team-create',
                        ])
                    @elseif ($modalForm === 'team-edit' && $modalTeam)
                        @include('teams._modal-form', [
                            'action' => route('teams.update', $modalTeam),
                            'team' => $modalTeam,
                            'modalForm' => 'team-edit',
                        ])
                    @else
                        <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                        <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                        <div x-html="content"></div>
                    @endif
                </div>
            </x-form-modal>
        </div>
    </x-main>
</x-app-layout>
