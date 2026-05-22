@forelse($notifications as $notification)
    @php
        $isRead = (bool) $notification->read;
        $accentClasses = match ($type) {
            'domain_expiry' => 'bg-sky-500',
            'status_change' => 'bg-emerald-500',
            default => 'bg-amber-500',
        };
        $iconClasses = match ($type) {
            'domain_expiry' => 'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-300/10 dark:text-sky-300 dark:ring-sky-300/20',
            'status_change' => 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-300/10 dark:text-emerald-300 dark:ring-emerald-300/20',
            default => 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-300/10 dark:text-amber-300 dark:ring-amber-300/20',
        };
    @endphp

    <x-container space="true"
        data-notification-card="{{ $type }}"
        class="{{ $isRead ? 'opacity-70' : '' }} notification-entry relative overflow-hidden border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80"
        id="{{ $notification->id }}">
        <span class="{{ $accentClasses }} notification-card-accent absolute inset-y-0 left-0 w-1" aria-hidden="true"></span>

        <div class="flex flex-col gap-4 pl-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <span class="{{ $iconClasses }} inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1">
                    @if ($type === 'domain_expiry')
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3c2.5 2.4 2.5 15.6 0 18" />
                        </svg>
                    @else
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V8a4 4 0 1 1 8 0v3" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11h10v9H7z" />
                        </svg>
                    @endif
                </span>

                <div class="min-w-0">
                    <x-paragraph bold=true class="{{ $isRead ? 'text-slate-500 dark:text-slate-400' : 'text-slate-950 dark:text-white' }} leading-6">
                        {{ $notification->translated_message }}
                    </x-paragraph>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                        @if ($isRead)
                            <x-badge type="info" class="bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ __('notifications.read') }}
                            </x-badge>
                        @endif
                    </div>
                </div>
            </div>

            @if (! $isRead)
                <x-primary-button class="mark-as-read-button shrink-0 !bg-emerald-600 px-3 py-2 text-sm !normal-case !tracking-normal hover:!bg-emerald-700 focus:!bg-emerald-700 focus:!ring-emerald-500 dark:!bg-emerald-500 dark:hover:!bg-emerald-400"
                    aria-label="{{ __('notifications.mark_as_read') }}"
                    @click="markAsRead(event, '{{ $notification->id }}', '{{ route('notifications.markAsRead', $notification) }}', '{{ $type }}')">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4 12 5 5L20 6" />
                    </svg>
                    {{ __('notifications.mark_as_read') }}
                </x-primary-button>
            @endif
        </div>
    </x-container>
@empty
    <p class="rounded-lg border border-dashed border-slate-300 bg-white/70 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">{{ __('notifications.no_notifications_of_this_type') }}</p>
@endforelse
