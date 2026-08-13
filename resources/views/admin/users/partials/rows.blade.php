@forelse ($users as $user)
    <x-table.row>
        <x-table.cell>{{ $user->name }}</x-table.cell>
        <x-table.cell>{{ $user->email }}</x-table.cell>
        <x-table.cell>
            @if ($user->hasVerifiedEmail())
                <x-badge type="success">{{ __('user.messages.email_verified') }}</x-badge>
            @else
                <x-badge type="danger">{{ __('user.messages.email_unverified') }}</x-badge>
            @endif
        </x-table.cell>
        <x-table.cell> {{ ucfirst($user->role->value) }} </x-table.cell>
        <x-table.cell>{{ $user->package?->monitoring_limit ?? '-' }}</x-table.cell>
        <x-table.cell><x-date-time :value="$user->created_at" format="date" /></x-table.cell>
        <x-table.cell><x-date-time :value="$user->updated_at" format="date" /></x-table.cell>
        <x-table.cell>
            <div class="flex items-center gap-2">
                <x-secondary-button
                    :href="route('admin.users.edit', $user)"
                    :icon-only="true"
                    data-form-modal-trigger
                    data-form-modal-name="admin-user-form-modal"
                    title="{{ __('button.edit') }}"
                    aria-label="{{ __('button.edit') }}"
                >
                    <x-icon name="pencil" class="h-4 w-4" />
                </x-secondary-button>
                @if ($user->id !== auth()->id())
                    <form
                        action="{{ route('admin.users.destroy', $user) }}"
                        method="POST"
                        class="inline-flex"
                        data-confirm-message="{{ __('user.delete.confirmation_question') }}"
                    >
                        @csrf
                        @method('DELETE')
                        <x-danger-button
                            :icon-only="true"
                            title="{{ __('button.delete') }}"
                            aria-label="{{ __('button.delete') }}"
                            data-testid="delete-user-{{ $user->id }}"
                        >
                            <x-icon name="trash" class="h-4 w-4" />
                        </x-danger-button>
                    </form>
                @endif
            </div>
        </x-table.cell>
    </x-table.row>
@empty
    <x-table.row>
        <x-table.cell colSpan="8" class="text-center text-gray-500"> {{ __('user.messages.empty') }} </x-table.cell>
    </x-table.row>
@endforelse
