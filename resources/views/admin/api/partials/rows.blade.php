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
