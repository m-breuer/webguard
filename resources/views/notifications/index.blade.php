<x-app-layout>
    @php
        $showReadEnabledQuery = request()->query();
        $showReadEnabledQuery['show_read'] = 1;

        $showReadDisabledQuery = request()->query();
        unset($showReadDisabledQuery['show_read']);
    @endphp

    <x-slot name="header">
        <x-heading type="h1">
            {{ __('notifications.title') }}
        </x-heading>

        <div class="space-6 items-center sm:ml-auto sm:flex">
            <label for="show_read" class="inline-flex items-center">
                <input type="checkbox" id="show_read" name="show_read" value="1"
                    class="shadow-xs focus:ring-3 rounded-sm border-gray-300 text-purple-600 focus:border-purple-300 focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600"
                    onchange="window.location.href = this.checked ? '{{ route('notifications.index', $showReadEnabledQuery) }}' : '{{ route('notifications.index', $showReadDisabledQuery) }}'"
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
        statusChangeOffset: {{ $statusBoardEntries->count() }},
        sslExpiryOffset: {{ $sslExpiryNotifications->count() }},
        deliveryHistoryOffset: {{ $deliveryHistory->count() }},
        currentLimit: {{ $limit }},
        isEmpty: {{ $sslExpiryNotifications->isEmpty() && $statusBoardEntries->isEmpty() && $deliveryHistory->isEmpty() ? 'true' : 'false' }},
        syncLimitWithUrl(limit) {
            const parsedLimit = Number.parseInt(limit, 10);
            const nextLimit = Number.isInteger(parsedLimit) && parsedLimit > 0 ? parsedLimit : 5;
            const url = new URL(window.location.href);
            url.searchParams.set('limit', String(nextLimit));
            const query = url.searchParams.toString();
            window.history.replaceState({}, '', query ? `${url.pathname}?${query}` : url.pathname);
        },
        updateEmptyState() {
            this.isEmpty = this.$root.querySelectorAll('.notification-entry').length === 0;
        },
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
                    } else if (type === 'delivery_history') {
                        this.deliveryHistoryOffset += response.data.count;
                        if (!response.data.hasMore) document.getElementById('delivery-history-load-more-container').style.display = 'none';
                    } else {
                        this.sslExpiryOffset += response.data.count;
                        if (!response.data.hasMore) document.getElementById('ssl-expiry-load-more-container').style.display = 'none';
                    }
                    const updatedOffset = type === 'status_change'
                        ? this.statusChangeOffset
                        : (type === 'delivery_history' ? this.deliveryHistoryOffset : this.sslExpiryOffset);
                    this.currentLimit = Math.max(this.currentLimit, updatedOffset);
                    this.syncLimitWithUrl(this.currentLimit);
                    this.updateEmptyState();
                });
        },
        markAsRead(event, notificationId, route, type) {
            event.preventDefault();
            axios.post(route)
                .then(() => {
                    const entry = document.getElementById(notificationId);
                    if (!entry) {
                        return;
                    }

                    entry.remove();
                    if (type === 'status_change') {
                        this.statusChangeOffset = Math.max(0, this.statusChangeOffset - 1);
                    } else {
                        this.sslExpiryOffset = Math.max(0, this.sslExpiryOffset - 1);
                    }
                    this.updateEmptyState();
                });
        }
    }" x-init="syncLimitWithUrl(currentLimit)">
        <x-container id="notifications-empty-state" x-cloak x-show="isEmpty">
            <x-paragraph>{{ __('notifications.no_notifications') }}</x-paragraph>
        </x-container>

        <div x-cloak x-show="!isEmpty">
            @if ($sslExpiryNotifications->isNotEmpty())
                <div class="mb-8">
                    <x-heading type="h2" space=true>{{ __('notifications.ssl_expiry_notifications') }}</x-heading>
                    <div id="ssl-expiry-notifications">
                        @include('notifications.partials.notification_list', [
                            'notifications' => $sslExpiryNotifications,
                            'type' => 'ssl_expiry',
                        ])
                    </div>
                    @if ($sslExpiryHasMore)
                        <div class="mt-4 text-center" id="ssl-expiry-load-more-container">
                            <x-primary-button
                                @click="loadMoreNotifications('ssl_expiry')">{{ __('notifications.load_more') }}</x-primary-button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($statusBoardEntries->isNotEmpty())
                <div class="mb-8">
                    <div id="status-change-notifications">
                        @include('notifications.partials.status_board_list', [
                            'entries' => $statusBoardEntries,
                        ])
                    </div>
                    @if ($statusChangeHasMore)
                        <div class="mt-4 text-center" id="status-change-load-more-container">
                            <x-primary-button
                                @click="loadMoreNotifications('status_change')">{{ __('notifications.load_more') }}</x-primary-button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($deliveryHistory->isNotEmpty())
                <div class="mb-8">
                    <x-heading type="h2" space=true>{{ __('notifications.delivery_history.heading') }}</x-heading>
                    <div id="delivery-history-notifications">
                        @include('notifications.partials.delivery_history_list', [
                            'deliveries' => $deliveryHistory,
                        ])
                    </div>
                    @if ($deliveryHistoryHasMore)
                        <div class="mt-4 text-center" id="delivery-history-load-more-container">
                            <x-primary-button
                                @click="loadMoreNotifications('delivery_history')">{{ __('notifications.load_more') }}</x-primary-button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-main>
</x-app-layout>
