<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('notifications.title') }}
        </x-heading>

        <div class="space-6 items-center sm:ml-auto sm:flex">
            <label for="show_read" class="inline-flex items-center">
                <input type="checkbox" id="show_read" name="show_read" value="1"
                    class="shadow-xs focus:ring-3 rounded-sm border-gray-300 text-purple-600 focus:border-purple-300 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
                    onchange="window.location.href = this.checked ? '{{ route('notifications.index', ['show_read' => true]) }}' : '{{ route('notifications.index') }}'"
                    {{ $showRead ? 'checked' : '' }}>
                <span class="ms-2">{{ __('notifications.show_read_notifications') }}</span>
            </label>

            <form method="POST" action="{{ route('notifications.markAllAsRead') }}" class="ms-2">
                @csrf
                <x-secondary-button type="submit">{{ __('notifications.mark_all_as_read') }}</x-secondary-button>
            </form>
        </div>
    </x-slot>

    <x-main x-data="{
        statusChangeOffset: {{ $statusChangeNotifications->count() }},
        sslExpiryOffset: {{ $sslExpiryNotifications->count() }},
        loadMoreNotifications(type) {
            let offset = type === 'status_change' ? this.statusChangeOffset : this.sslExpiryOffset;
            axios.post('{{ route('notifications.loadMore') }}', {
                    type: type,
                    offset: offset,
                    show_read: {{ $showRead ? 'true' : 'false' }}
                })
                .then(response => {
                    document.getElementById(type.replace('_', '-') + '-notifications').insertAdjacentHTML('beforeend', response.data.html);
                    if (type === 'status_change') {
                        this.statusChangeOffset += response.data.count;
                        if (!response.data.hasMore) document.getElementById('status-change-load-more-container').style.display = 'none';
                    } else {
                        this.sslExpiryOffset += response.data.count;
                        if (!response.data.hasMore) document.getElementById('ssl-expiry-load-more-container').style.display = 'none';
                    }
                });
        },
        markAsRead(event, notificationId, route) {
            event.preventDefault();
            axios.post(route, { _method: 'POST', notification_id: notificationId })
                .then(() => document.getElementById(notificationId).remove());
        }
    }">
        @if ($sslExpiryNotifications->isEmpty() && $statusChangeNotifications->isEmpty())
            <x-container>
                <x-paragraph>{{ __('notifications.no_notifications') }}</x-paragraph>
            </x-container>
        @else
            @if ($sslExpiryNotifications->isNotEmpty())
                <div class="mb-8">
                    <x-heading type="h2" space=true>{{ __('notifications.ssl_expiry_notifications') }}</x-heading>
                    <div id="ssl-expiry-notifications">
                        @include('notifications.partials.notification_list', [
                            'notifications' => $sslExpiryNotifications,
                            'type' => 'ssl_expiry',
                        ])
                    </div>
                    @if ($sslExpiryNotifications->count() >= 5)
                        <div class="mt-4 text-center" id="ssl-expiry-load-more-container">
                            <x-primary-button
                                @click="loadMoreNotifications('ssl_expiry')">{{ __('notifications.load_more') }}</x-primary-button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($statusChangeNotifications->isNotEmpty())
                <div class="mb-8">
                    <x-heading type="h2"
                        space=true>{{ __('notifications.status_change_notifications') }}</x-heading>
                    <div id="status-change-notifications">
                        @include('notifications.partials.notification_list', [
                            'notifications' => $statusChangeNotifications,
                            'type' => 'status_change',
                        ])
                    </div>
                    @if ($statusChangeNotifications->count() >= 5)
                        <div class="mt-4 text-center" id="status-change-load-more-container">
                            <x-primary-button
                                @click="loadMoreNotifications('status_change')">{{ __('notifications.load_more') }}</x-primary-button>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </x-main>
</x-app-layout>
