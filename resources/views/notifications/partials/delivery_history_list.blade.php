@php
    use App\Enums\NotificationDeliveryStatus;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;
@endphp

@forelse($deliveries as $delivery)
    @php
        $monitoring = $delivery->monitoringNotification?->monitoring;
        $monitoringName = $monitoring?->name ?? data_get($delivery->payload, 'monitoring.name') ?? __('notifications.labels.not_available');
        $monitoringTarget = $monitoring?->target ?? data_get($delivery->payload, 'monitoring.target');
        $statusBadgeType = match ($delivery->status) {
            NotificationDeliveryStatus::SENT => 'success',
            NotificationDeliveryStatus::FAILED => 'danger',
            NotificationDeliveryStatus::SKIPPED => 'warning',
        };
        $statusAccent = match ($delivery->status) {
            NotificationDeliveryStatus::SENT => 'bg-emerald-500',
            NotificationDeliveryStatus::FAILED => 'bg-red-500',
            NotificationDeliveryStatus::SKIPPED => 'bg-amber-500',
        };
        $errorMessage = $delivery->error_message ? Str::limit($delivery->error_message, 180) : null;
    @endphp

    <x-container space="true" data-notification-card="delivery_history" class="notification-entry relative overflow-hidden border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80" id="{{ $delivery->id }}">
        <span class="{{ $statusAccent }} notification-card-accent absolute inset-y-0 left-0 w-1" aria-hidden="true"></span>

        <div class="flex flex-col gap-5 pl-2 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-300/10 dark:text-violet-300 dark:ring-violet-300/20">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M7 12h10M9 17h6" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-heading type="h3" class="truncate text-lg font-semibold text-slate-950 dark:text-white">
                            {{ $monitoringName }}
                        </x-heading>
                        <x-badge type="{{ $statusBadgeType }}" class="border border-black/10 px-3 py-1 text-sm dark:border-white/20">
                            {{ __('notifications.delivery_status.' . $delivery->status->value) }}
                        </x-badge>
                    </div>

                    @if ($monitoringTarget)
                        <x-paragraph class="mt-2 break-all text-sm text-slate-600 dark:text-slate-300">
                            {{ $monitoringTarget }}
                        </x-paragraph>
                    @endif

                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-600 dark:text-slate-300 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('notifications.labels.channel') }}</dt>
                            <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ __('notifications.channels.' . $delivery->channel) }}</dd>
                        </div>
                        <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('notifications.labels.event') }}</dt>
                            <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ __('notifications.events.' . $delivery->event_type) }}</dd>
                        </div>
                        <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('notifications.labels.attempted_at') }}</dt>
                            <dd class="mt-1 text-slate-800 dark:text-slate-100">@if ($delivery->created_at)<x-date-time :value="$delivery->created_at" />@else{{ __('notifications.labels.not_available') }}@endif</dd>
                        </div>
                        @if ($delivery->status === NotificationDeliveryStatus::SENT)
                            <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-800/70">
                                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ __('notifications.labels.sent_at') }}</dt>
                                <dd class="mt-1 text-slate-800 dark:text-slate-100">@if ($delivery->sent_at)<x-date-time :value="$delivery->sent_at" />@else{{ __('notifications.labels.not_available') }}@endif</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($errorMessage)
                        <x-paragraph class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-400/20 dark:bg-red-400/10 dark:text-red-300">
                            <x-span class="font-semibold">{{ __('notifications.labels.error') }}:</x-span>
                            <x-span>{{ $errorMessage }}</x-span>
                        </x-paragraph>
                    @endif
                </div>
            </div>
        </div>
    </x-container>
@empty
    <p class="rounded-lg border border-dashed border-slate-300 bg-white/70 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">{{ __('notifications.no_notifications_of_this_type') }}</p>
@endforelse
