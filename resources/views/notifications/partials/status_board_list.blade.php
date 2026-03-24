@php
    use Illuminate\Support\Carbon;
@endphp

@forelse($entries as $entry)
    @php
        $statusLabel = __($entry['status_key']);
        $statusWithCode = $entry['latest_status_code'] ? $entry['latest_status_code'] . ' ' . $statusLabel : $statusLabel;
        $latestCheckedAt = $entry['latest_checked_at']
            ? Carbon::parse($entry['latest_checked_at'])->locale(app()->getLocale())->isoFormat('L LT')
            : __('notifications.labels.not_available');
        $latestStatusChangeAt = $entry['latest_status_change_at']
            ? Carbon::parse($entry['latest_status_change_at'])->locale(app()->getLocale())->isoFormat('L LT')
            : __('notifications.labels.not_available');
    @endphp
    <x-container space="true"
        class="{{ $entry['read'] ? ' !text-gray-500 dark:!text-gray-500' : '' }} notification-board-entry mb-3"
        id="{{ $entry['notification_id'] }}">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0 space-y-2">
                <x-heading type="h3" class="truncate text-lg">
                    {{ $entry['monitor_name'] }}
                </x-heading>
                <div class="grid grid-cols-1 gap-1 text-sm text-gray-600 dark:text-gray-300">
                    <x-paragraph class="break-all">
                        <x-span class="font-semibold">{{ __('notifications.labels.host') }}:</x-span>
                        <x-span>{{ $entry['target'] }}</x-span>
                    </x-paragraph>
                    <x-paragraph>
                        <x-span class="font-semibold">{{ __('notifications.labels.monitor') }}:</x-span>
                        <x-span>{{ strtoupper($entry['type']) }}</x-span>
                    </x-paragraph>
                    <x-paragraph>
                        <x-span class="font-semibold">{{ __('notifications.labels.timestamp') }}:</x-span>
                        <x-span>{{ $latestCheckedAt }}</x-span>
                    </x-paragraph>
                    <x-paragraph>
                        <x-span class="font-semibold">{{ __('notifications.labels.latest_status_change') }}:</x-span>
                        <x-span>{{ $latestStatusChangeAt }}</x-span>
                    </x-paragraph>
                </div>
                <x-paragraph class="text-sm font-medium text-gray-700 dark:text-gray-100">
                    {{ __($entry['status_change_key']) }}
                </x-paragraph>
            </div>

            <div class="flex flex-col items-start gap-2 md:items-end">
                <x-badge type="{{ $entry['badge_type'] }}"
                    title="{{ __('notifications.tooltips.latest_status', ['status' => $statusWithCode]) }}"
                    class="border border-black/10 px-3 py-1 text-sm dark:border-white/20">
                    @if ($entry['latest_status_code'])
                        {{ $entry['latest_status_code'] }}
                    @else
                        {{ __('notifications.labels.no_status_code') }}
                    @endif
                </x-badge>
                <x-badge type="{{ $entry['badge_type'] }}"
                    title="{{ __('notifications.tooltips.latest_status', ['status' => $statusWithCode]) }}"
                    class="border border-black/10 px-3 py-1 text-sm dark:border-white/20">
                    {{ $statusLabel }}
                </x-badge>
                @if (!$entry['read'])
                    <x-primary-button class="mark-as-read-button text-xs"
                        @click="markAsRead(event, '{{ $entry['notification_id'] }}', '{{ route('notifications.markAsRead', $entry['notification_id']) }}')">{{ __('notifications.mark_as_read') }}</x-primary-button>
                @endif
            </div>
        </div>
    </x-container>
@empty
    <p>{{ __('notifications.no_notifications_of_this_type') }}</p>
@endforelse
