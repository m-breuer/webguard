<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('user.title') }}
        </x-heading>

        <div class="flex items-center space-x-2">
            <x-primary-button :href="route('admin.users.create')" class="sm:ml-auto">
                {{ __('button.create') }}
            </x-primary-button>

            <x-secondary-button :href="route('admin.dashboard')">
                {{ __('button.back') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <x-main>
        <div class="flex items-center justify-between">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center space-x-2">
                <x-text-input type="text" name="search" :value="request('search')"
                    placeholder="{{ __('search.fields.placeholder', ['attribute' => __('user.title')]) }}"
                    class="w-full max-w-sm" />
            </form>
        </div>

        <x-table class="mt-4">
            <x-slot name="head">
                <x-table.heading>{{ __('user.fields.name') }}</x-table.heading>
                <x-table.heading>{{ __('user.fields.email') }}</x-table.heading>
                <x-table.heading>{{ __('user.fields.role') }}</x-table.heading>
                <x-table.heading>{{ __('user.fields.monitoring_limit') }}</x-table.heading>
                <x-table.heading>{{ __('user.fields.created_at') }}</x-table.heading>
                <x-table.heading>{{ __('user.fields.updated_at') }}</x-table.heading>
                <x-table.heading>{{ __('user.actions.edit') . ' / ' . __('button.delete') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @forelse ($users as $user)
                    <x-table.row>
                        <x-table.cell>{{ $user->name }}</x-table.cell>
                        <x-table.cell>{{ $user->email }}</x-table.cell>
                        <x-table.cell>
                            {{ ucfirst($user->role->value) }}
                        </x-table.cell>
                        <x-table.cell>{{ $user->package->monitoring_limit }}</x-table.cell>
                        <x-table.cell>{{ $user->created_at->format('d.m.Y') }}</x-table.cell>
                        <x-table.cell>{{ $user->updated_at->format('d.m.Y') }}</x-table.cell>
                        <x-table.cell>
                            <a href="{{ route('admin.users.edit', $user) }}"
                                class="text-purple-600 hover:underline">{{ __('button.edit') }}</a>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('{{ __('user.delete.confirmation_question') }}')"
                                    class="ml-2 text-red-600 hover:underline">{{ __('button.delete') }}</button>
                            </form>
                        </x-table.cell>
                    </x-table.row>

                @empty
                    <x-table.row>
                        <x-table.cell colSpan="7" class="text-center text-gray-500">
                            {{ __('user.messages.empty') }}
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </x-main>
</x-app-layout>
