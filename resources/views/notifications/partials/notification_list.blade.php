@forelse($notifications as $notification)
    <x-container space="true"
        class="{{ !$notification->read ?: ' !text-gray-500 dark:!text-gray-500' }} block items-center justify-between sm:flex"
        id="{{ $notification->id }}">
        <div class="mb-4 sm:mb-0">
            <x-paragraph bold=true>{{ $notification->translated_message }}</x-paragraph>
            <x-paragraph class="text-sm">{{ $notification->created_at->diffForHumans() }}</x-paragraph>
        </div>

        @if (!$notification->read)
            <x-primary-button class="mark-as-read-button text-sm"
                @click="markAsRead(event, '{{ $notification->id }}', '{{ route('notifications.markAsRead', $notification) }}')">{{ __('notifications.mark_as_read') }}</x-primary-button>
        @endif
    </x-container>
@empty
    <p>{{ __('notifications.no_notifications_of_this_type') }}</p>
@endforelse
