@php
    use App\Enums\TeamRole;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ $team->name }}</x-heading>
            @if ($team->description)
                <x-paragraph>{{ $team->description }}</x-paragraph>
            @endif
        </div>

        <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}"
            class="ml-auto flex flex-wrap gap-2">
            @if ($isTeamAdmin)
                <x-secondary-button :href="route('teams.edit', $team)"
                    data-form-modal-trigger data-form-modal-name="team-form-modal">
                    {{ __('team.actions.edit') }}
                </x-secondary-button>
                <form method="POST" action="{{ route('teams.destroy', $team) }}"
                    data-confirm-message="{{ __('team.actions.delete') }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>
                        {{ __('team.actions.delete') }}
                    </x-danger-button>
                </form>
            @endif
            <form method="POST" action="{{ route('teams.leave', $team) }}">
                @csrf
                @method('DELETE')
                <x-secondary-button>
                    {{ __('team.actions.leave') }}
                </x-secondary-button>
            </form>
            <x-secondary-button :href="route('teams.index')">
                {{ __('button.back') }}
            </x-secondary-button>

            <x-form-modal name="team-form-modal" title="{{ __('team.edit.title', ['team' => $team->name]) }}"
                description="{{ __('team.title') }}" max-width="3xl">
                <div class="p-6" x-ref="content">
                    <p x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.loading') }}</p>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                    <div x-html="content"></div>
                </div>
            </x-form-modal>
        </div>
    </x-slot>

    <x-main>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-container>
                <x-heading type="h2">{{ __('team.sections.members') }}</x-heading>

                <div class="mt-4 space-y-3">
                    @foreach ($team->memberships as $membership)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3 last:border-b-0 dark:border-gray-700">
                            <div>
                                <x-paragraph class="font-semibold">{{ $membership->user->name }}</x-paragraph>
                                <x-paragraph class="text-sm text-gray-500">{{ $membership->user->email }}</x-paragraph>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($isTeamAdmin)
                                    <form method="POST" action="{{ route('teams.members.update', [$team, $membership]) }}"
                                        class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <x-select-input name="role" class="min-w-32">
                                            @foreach (TeamRole::cases() as $role)
                                                <option value="{{ $role->value }}" @selected($membership->role === $role)>
                                                    {{ __('team.roles.' . $role->value) }}
                                                </option>
                                            @endforeach
                                        </x-select-input>
                                        <x-secondary-button>
                                            {{ __('team.actions.save_role') }}
                                        </x-secondary-button>
                                    </form>

                                    <form method="POST" action="{{ route('teams.members.destroy', [$team, $membership]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button>
                                            {{ __('team.actions.remove') }}
                                        </x-danger-button>
                                    </form>
                                @else
                                    <x-badge type="info">{{ __('team.roles.' . $membership->role->value) }}</x-badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-container>

            <div class="space-y-6">
                @if ($isTeamAdmin)
                    <x-container>
                        <x-heading type="h2">{{ __('team.sections.invite') }}</x-heading>

                        <form method="POST" action="{{ route('teams.invitations.store', $team) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="email" :value="__('team.fields.email')" />
                                <x-text-input id="email" type="email" name="email" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" />
                            </div>
                            <div>
                                <x-input-label for="role" :value="__('team.fields.role')" />
                                <x-select-input id="role" name="role" required>
                                    @foreach (TeamRole::cases() as $role)
                                        <option value="{{ $role->value }}">{{ __('team.roles.' . $role->value) }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-input-error :messages="$errors->get('role')" />
                            </div>
                            <x-primary-button>
                                {{ __('team.actions.invite') }}
                            </x-primary-button>
                        </form>
                    </x-container>
                @endif

                <x-container>
                    <x-heading type="h2">{{ __('team.sections.invitations') }}</x-heading>

                    @if ($team->invitations->count() === 0)
                        <x-paragraph class="mt-3">{{ __('team.empty.invitations') }}</x-paragraph>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($team->invitations as $invitation)
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3 last:border-b-0 dark:border-gray-700">
                                    <div>
                                        <x-paragraph class="font-semibold">{{ $invitation->email }}</x-paragraph>
                                        <x-paragraph class="text-sm text-gray-500">{{ __('team.roles.' . $invitation->role->value) }}</x-paragraph>
                                    </div>

                                    @if ($isTeamAdmin)
                                        <form method="POST" action="{{ route('teams.invitations.destroy', [$team, $invitation]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button>
                                                {{ __('team.actions.revoke') }}
                                            </x-danger-button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-container>
            </div>
        </div>
    </x-main>
</x-app-layout>
