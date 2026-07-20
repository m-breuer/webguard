<div data-analytics-section-content>
    <x-container id="monitoring-groups" class="overflow-hidden">
        <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
            <x-heading type="h2">{{ __('incidents.analytics.overview.groups') }}</x-heading>
            <a href="{{ route('monitoring-groups.index') }}" class="text-sm font-semibold text-purple-700 dark:text-purple-300">{{ __('incidents.analytics.overview.view_all_groups') }}</a>
        </div>
        @if ($groups->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('incidents.analytics.overview.empty_groups') }}</p>
        @else
            <div class="mt-2 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($groups as $group)
                    @php($summary = $group['summary'])
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div class="min-w-0"><a href="{{ route('monitorings.index', ['group_id' => $group['model']->id]) }}" class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $group['model']->name }}</a><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ trans_choice('monitoring_group.monitorings_count', $summary['total'], ['count' => $summary['total']]) }} · {{ $summary['healthy'] }} {{ __('dashboard.summary.healthy') }}</p></div>
                        <span class="text-sm font-semibold {{ $summary['down'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ __('incidents.analytics.overview.status.' . $summary['state']) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-container>
</div>
