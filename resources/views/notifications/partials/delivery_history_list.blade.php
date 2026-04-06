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
        $attemptedAt = $delivery->created_at
            ? $delivery->created_at->locale(app()->getLocale())->isoFormat('L LT')
            : __('notifications.labels.not_available');
        $sentAt = $delivery->sent_at
            ? $delivery->sent_at->locale(app()->getLocale())->isoFormat('L LT')
            : __('notifications.labels.not_available');
        $statusBadgeType = match ($delivery->status) {
            NotificationDeliveryStatus::SENT => 'success',
            NotificationDeliveryStatus::FAILED => 'danger',
            NotificationDeliveryStatus::SKIPPED => 'warning',
        };
        $errorMessage = $delivery->error_message ? Str::limit($delivery->error_message, 180) : null;
    @endphp

    <x-container space="true" class="notification-entry mb-3" id="{{ $delivery->id }}">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-heading type="h3" class="truncate text-lg">
                        {{ $monitoringName }}
                    </x-heading>
                    <x-badge type="{{ $statusBadgeType }}" class="border border-black/10 px-3 py-1 text-sm dark:border-white/20">
                        {{ __('notifications.delivery_status.' . $delivery->status->value) }}
                    </x-badge>
                </div>

                <div class="grid grid-cols-1 gap-1 text-sm text-gray-600 dark:text-gray-300">
                    @if ($monitoringTarget)
                        <x-paragraph class="break-all">
                            <x-span class="font-semibold">{{ __('notifications.labels.host') }}:</x-span>
                            <x-span>{{ $monitoringTarget }}</x-span>
                        </x-paragraph>
                    @endif
                    <x-paragraph>
                        <x-span class="font-semibold">{{ __('notifications.labels.channel') }}:</x-span>
                        <x-span>{{ __('notifications.channels.' . $delivery->channel) }}</x-span>
                    </x-paragraph>
                    <x-paragraph>
                        <x-span class="font-semibold">{{ __('notifications.labels.event') }}:</x-span>
                        <x-span>{{ __('notifications.events.' . $delivery->event_type) }}</x-span>
                    </x-paragraph>
                    <x-paragraph>
                        <x-span class="font-semibold">{{ __('notifications.labels.attempted_at') }}:</x-span>
                        <x-span>{{ $attemptedAt }}</x-span>
                    </x-paragraph>
                    @if ($delivery->status === NotificationDeliveryStatus::SENT)
                        <x-paragraph>
                            <x-span class="font-semibold">{{ __('notifications.labels.sent_at') }}:</x-span>
                            <x-span>{{ $sentAt }}</x-span>
                        </x-paragraph>
                    @endif
                    @if ($errorMessage)
                        <x-paragraph class="text-sm text-red-600 dark:text-red-400">
                            <x-span class="font-semibold">{{ __('notifications.labels.error') }}:</x-span>
                            <x-span>{{ $errorMessage }}</x-span>
                        </x-paragraph>
                    @endif
                </div>
            </div>
        </div>
    </x-container>
@empty
    <p>{{ __('notifications.no_notifications_of_this_type') }}</p>
@endforelse
