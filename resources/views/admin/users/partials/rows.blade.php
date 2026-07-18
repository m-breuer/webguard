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
        <x-table.cell>
            {{ ucfirst($user->role->value) }}
        </x-table.cell>
        <x-table.cell>{{ $user->package?->monitoring_limit ?? '-' }}</x-table.cell>
        <x-table.cell>{{ $user->created_at->format('d.m.Y') }}</x-table.cell>
        <x-table.cell>{{ $user->updated_at->format('d.m.Y') }}</x-table.cell>
        <x-table.cell>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.edit', $user) }}"
                    class="inline-flex min-h-10 items-center text-purple-600 hover:underline"
                    data-form-modal-trigger data-form-modal-name="admin-user-form-modal">
                    {{ __('button.edit') }}
                </a>
                @if ($user->id !== auth()->id())
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-flex"
                        data-confirm-message="{{ __('user.delete.confirmation_question') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex min-h-10 cursor-pointer items-center text-red-600 hover:underline"
                            data-testid="delete-user-{{ $user->id }}">{{ __('button.delete') }}</button>
                    </form>
                @endif
            </div>
        </x-table.cell>
    </x-table.row>
@empty
    <x-table.row>
        <x-table.cell colSpan="8" class="text-center text-gray-500">
            {{ __('user.messages.empty') }}
        </x-table.cell>
    </x-table.row>
@endforelse
