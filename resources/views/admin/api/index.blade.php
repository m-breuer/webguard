<x-app-layout>
    <x-slot name="header">
        <x-heading>{{ __('api.logs.title') }}</x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <div x-data class="mb-4 flex flex-wrap items-center gap-2">
            <x-input-label for="user_id">{{ __('user.title') }}:</x-input-label>
            <select id="user_id"
                class="rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                @change="
                        const url = new URL(window.location.href);
                        if ($event.target.value) {
                            url.searchParams.set('user_id', $event.target.value);
                        } else {
                            url.searchParams.delete('user_id');
                        }
                        window.location.href = url.toString();
                    ">
                <option value="">{{ __('search.filter.all') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') === $user->id)>
                        {{ $user->email }}
                    </option>
                @endforeach
            </select>
        </div>

        <x-table>
            <x-slot name="head">
                <x-table.heading>{{ __('api.logs.fields.date') }}</x-table.heading>
                <x-table.heading>{{ __('user.fields.email') }}</x-table.heading>
                <x-table.heading>{{ __('api.logs.fields.endpoint') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @forelse ($apiLogs as $log)
                    <x-table.row>
                        <x-table.cell>{{ $log->created_at }}</x-table.cell>
                        <x-table.cell>{{ $log->user->email }}</x-table.cell>
                        <x-table.cell>{{ $log->route }}</x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colSpan="3" class="text-center text-gray-500">
                            {{ __('api.logs.messages.no_logs') }}
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $apiLogs->withQueryString()->links() }}
        </div>
    </x-main>
</x-app-layout>
