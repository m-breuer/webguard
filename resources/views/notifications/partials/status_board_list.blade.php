@forelse ($entries as $entry)
    @php
        $statusLabel = __($entry['status_key']);
        $statusWithCode = $entry['latest_status_code'] ? $entry['latest_status_code'] . ' ' . $statusLabel : $statusLabel;
        $isRead = (bool) $entry['read'];
    @endphp
    <x-container
        space="true"
        data-notification-card="status_change"
        class="{{ $isRead ? 'opacity-70' : '' }} notification-board-entry notification-entry relative overflow-hidden border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80"
        id="{{ $entry['notification_id'] }}"
    >
        <span class="notification-card-accent absolute inset-y-0 left-0 w-1 bg-emerald-500" aria-hidden="true"></span>

        <div class="flex flex-col gap-5 pl-2 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-heading
                            type="h3"
                            class="{{ $isRead ? 'text-slate-500 dark:text-slate-400' : 'text-slate-950 dark:text-white' }} truncate text-lg font-semibold"
                        >
                            {{ $entry['monitor_name'] }}
                        </x-heading>
                        @if ($isRead)
                            <x-badge
                                type="info"
                                class="bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                            >
                                {{ __('notifications.read') }}
                            </x-badge>
                        @endif
                    </div>

                    <x-paragraph class="mt-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __($entry['status_change_key']) }}
                    </x-paragraph>

                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-600 md:grid-cols-2 dark:text-slate-300">
                        <div class="min-w-0 rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold tracking-[0.08em] text-slate-500 uppercase dark:text-slate-400">
                                {{ __('notifications.labels.host') }}
                            </dt>
                            <dd class="mt-1 break-all text-slate-800 dark:text-slate-100">{{ $entry['target'] }}</dd>
                        </div>
                        <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold tracking-[0.08em] text-slate-500 uppercase dark:text-slate-400">
                                {{ __('notifications.labels.monitor') }}
                            </dt>
                            <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ strtoupper($entry['type']) }}</dd>
                        </div>
                        <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold tracking-[0.08em] text-slate-500 uppercase dark:text-slate-400">
                                {{ __('notifications.labels.timestamp') }}
                            </dt>
                            <dd class="mt-1 text-slate-800 dark:text-slate-100">
                                @if ($entry['latest_checked_at'])
                                    <x-date-time :value="$entry['latest_checked_at']" />
                                @else
                                    {{ __('notifications.labels.not_available') }}
                                @endif
                            </dd>
                        </div>
                        <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold tracking-[0.08em] text-slate-500 uppercase dark:text-slate-400">
                                {{ __('notifications.labels.latest_status_change') }}
                            </dt>
                            <dd class="mt-1 text-slate-800 dark:text-slate-100">
                                @if ($entry['latest_status_change_at'])
                                    <x-date-time :value="$entry['latest_status_change_at']" />
                                @else
                                    {{ __('notifications.labels.not_available') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="flex w-full shrink-0 flex-wrap items-center gap-2 lg:w-auto lg:flex-col lg:items-end">
                <x-badge
                    type="{{ $entry['badge_type'] }}"
                    title="{{ __('notifications.tooltips.latest_status', ['status' => $statusWithCode]) }}"
                    class="border border-black/10 px-3 py-1 text-sm dark:border-white/20"
                >
                    @if ($entry['latest_status_code'])
                        {{ $entry['latest_status_code'] }}
                    @else
                        {{ __('notifications.labels.no_status_code') }}
                    @endif
                </x-badge>
                <x-badge
                    type="{{ $entry['badge_type'] }}"
                    title="{{ __('notifications.tooltips.latest_status', ['status' => $statusWithCode]) }}"
                    class="border border-black/10 px-3 py-1 text-sm dark:border-white/20"
                >
                    {{ $statusLabel }}
                </x-badge>
                @if (! $isRead)
                    <x-primary-button
                        :icon-only="true"
                        class="mark-as-read-button !bg-purple-600 hover:!bg-purple-700 focus:!bg-purple-700 focus:!ring-purple-500 dark:!bg-purple-500 dark:!text-white dark:hover:!bg-purple-400 dark:focus:!ring-purple-400"
                        title="{{ __('notifications.mark_as_read') }}"
                        aria-label="{{ __('notifications.mark_as_read') }}"
                        @click="markAsRead(event, '{{ $entry['notification_id'] }}', '{{ route('notifications.markAsRead', $entry['notification_id']) }}', 'status_change')"
                    >
                        <x-icon name="check" class="h-4 w-4" />
                    </x-primary-button>
                @endif
            </div>
        </div>
    </x-container>
@empty
    <p class="rounded-lg border border-dashed border-slate-300 bg-white/70 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
        {{ __('notifications.no_notifications_of_this_type') }}
    </p>
@endforelse
