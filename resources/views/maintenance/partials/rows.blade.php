@forelse ($monitorings as $monitoring)
    <x-table.row class="align-top">
        <x-table.cell>
            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $monitoring->name }}</div>
            <div class="mt-1 max-w-md truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $monitoring->target }}">
                {{ $monitoring->target }}
            </div>
        </x-table.cell>
        <x-table.cell>
            @if ($monitoring->isUnderMaintenance())
                <x-badge type="info">{{ __('maintenance.status.active') }}</x-badge>
            @elseif ($monitoring->maintenance_from && $monitoring->maintenance_from->isFuture())
                <x-badge type="warning">{{ __('maintenance.status.upcoming') }}</x-badge>
            @elseif ($monitoring->maintenance_from)
                <x-badge type="neutral">{{ __('maintenance.status.expired') }}</x-badge>
            @else
                <x-badge type="success">{{ __('maintenance.status.none') }}</x-badge>
            @endif
        </x-table.cell>
        <x-table.cell>{{ $monitoring->maintenance_from?->format('Y-m-d H:i') ?? '-' }}</x-table.cell>
        <x-table.cell>
            {{ $monitoring->maintenance_until?->format('Y-m-d H:i') ?? ($monitoring->maintenance_from ? __('maintenance.status.open_ended') : '-') }}
        </x-table.cell>
        <x-table.cell>
            @if ($monitoring->groups->isNotEmpty())
                <div class="flex max-w-sm flex-wrap gap-1.5">
                    @foreach ($monitoring->groups as $group)
                        <x-badge type="neutral">{{ $group->name }}</x-badge>
                    @endforeach
                </div>
            @else
                <span class="text-gray-500 dark:text-gray-400">-</span>
            @endif
        </x-table.cell>
        @if ($canManageMaintenance)
            <x-table.cell>
                @if ($monitoring->maintenance_from && in_array($monitoring->id, $manageableMonitoringIds, true))
                    <form method="POST" action="{{ route('maintenance.destroy') }}" class="inline-flex">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="monitoring_id" value="{{ $monitoring->id }}">
                        <button
                            type="submit"
                            class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md text-purple-600 transition hover:bg-purple-50 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:text-purple-300 dark:hover:bg-purple-950/40"
                            title="{{ __('maintenance.actions.clear') }}"
                            aria-label="{{ __('maintenance.actions.clear') }}"
                            x-data
                            x-on:click.prevent="if (confirm('{{ __('maintenance.actions.clear_confirmation') }}')) $el.closest('form').submit()">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </form>
                @else
                    <span class="text-gray-500 dark:text-gray-400">-</span>
                @endif
            </x-table.cell>
        @endif
    </x-table.row>
@empty
    <x-table.row>
        <x-table.cell colspan="{{ $canManageMaintenance ? 6 : 5 }}" class="text-center text-gray-500">
            {{ __('maintenance.empty.text') }}
        </x-table.cell>
    </x-table.row>
@endforelse
