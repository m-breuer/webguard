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
            <a href="{{ route('admin.users.edit', $user) }}" class="text-purple-600 hover:underline">
                {{ __('button.edit') }}
            </a>
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('user.delete.confirmation_question') }}')"
                    class="ml-2 text-red-600 hover:underline">{{ __('button.delete') }}</button>
            </form>
        </x-table.cell>
    </x-table.row>
@empty
    <x-table.row>
        <x-table.cell colSpan="8" class="text-center text-gray-500">
            {{ __('user.messages.empty') }}
        </x-table.cell>
    </x-table.row>
@endforelse
