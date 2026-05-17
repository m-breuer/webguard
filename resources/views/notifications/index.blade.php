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
        statusChangeOffset: 0,
        sslExpiryOffset: 0,
        domainExpiryOffset: 0,
        deliveryHistoryOffset: 0,
        currentLimit: {{ $limit }},
        isEmpty: false,
        isLoading: true,
        sections: [
            {
                type: 'ssl_expiry',
                sectionId: 'ssl-expiry-section',
                containerId: 'ssl-expiry-notifications',
                loadMoreContainerId: 'ssl-expiry-load-more-container',
            },
            {
                type: 'domain_expiry',
                sectionId: 'domain-expiry-section',
                containerId: 'domain-expiry-notifications',
                loadMoreContainerId: 'domain-expiry-load-more-container',
            },
            {
                type: 'status_change',
                sectionId: 'status-change-section',
                containerId: 'status-change-notifications',
                loadMoreContainerId: 'status-change-load-more-container',
            },
            {
                type: 'delivery_history',
                sectionId: 'delivery-history-section',
                containerId: 'delivery-history-notifications',
                loadMoreContainerId: 'delivery-history-load-more-container',
            },
        ],
        getOffsetForType(type) {
            if (type === 'status_change') {
                return this.statusChangeOffset;
            }

            if (type === 'domain_expiry') {
                return this.domainExpiryOffset;
            }

            if (type === 'delivery_history') {
                return this.deliveryHistoryOffset;
            }

            return this.sslExpiryOffset;
        },
        setOffsetForType(type, offset) {
            if (type === 'status_change') {
                this.statusChangeOffset = offset;
            } else if (type === 'domain_expiry') {
                this.domainExpiryOffset = offset;
            } else if (type === 'delivery_history') {
                this.deliveryHistoryOffset = offset;
            } else {
                this.sslExpiryOffset = offset;
            }
        },
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
        sectionForType(type) {
            return this.sections.find((section) => section.type === type);
        },
        loadInitialNotifications() {
            this.isLoading = true;

            Promise.all(this.sections.map((section) => this.loadNotificationSection(section.type, true)))
                .finally(() => {
                    this.isLoading = false;
                    this.updateEmptyState();
                });
        },
        loadNotificationSection(type, initial = false) {
            const section = this.sectionForType(type);
            const offset = initial ? 0 : this.getOffsetForType(type);
            const payload = {
                type: type,
                offset: offset,
                show_read: {{ $showRead ? 'true' : 'false' }},
            };

            if (initial) {
                payload.limit = this.currentLimit;
            }

            return axios.post('{{ route('notifications.loadMore') }}', payload)
                .then(response => {
                    const sectionElement = document.getElementById(section.sectionId);
                    const container = document.getElementById(section.containerId);
                    const loadMoreContainer = document.getElementById(section.loadMoreContainerId);

                    if (initial) {
                        container.innerHTML = '';
                    }

                    container.insertAdjacentHTML('beforeend', response.data.html);

                    const nextOffset = initial
                        ? response.data.count
                        : this.getOffsetForType(type) + response.data.count;
                    this.setOffsetForType(type, nextOffset);

                    sectionElement.style.display = response.data.count > 0 ? '' : 'none';
                    loadMoreContainer.style.display = response.data.hasMore ? '' : 'none';

                    if (!initial) {
                        this.currentLimit = Math.max(this.currentLimit, nextOffset);
                        this.syncLimitWithUrl(this.currentLimit);
                    }

                    this.updateEmptyState();
                });
        },
        loadMoreNotifications(type) {
            this.loadNotificationSection(type);
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
                    this.setOffsetForType(type, Math.max(0, this.getOffsetForType(type) - 1));
                    this.updateEmptyState();

                    const section = this.sectionForType(type);
                    if (section && document.getElementById(section.containerId).querySelectorAll('.notification-entry').length === 0) {
                        document.getElementById(section.sectionId).style.display = 'none';
                    }
                });
        }
    }" x-init="syncLimitWithUrl(currentLimit); loadInitialNotifications()">
        <x-container id="notifications-loading-state" x-cloak x-show="isLoading">
            <x-loading-indicator />
        </x-container>

        <x-container id="notifications-empty-state" x-cloak x-show="!isLoading && isEmpty">
            <x-paragraph>{{ __('notifications.no_notifications') }}</x-paragraph>
        </x-container>

        <div x-cloak x-show="!isLoading && !isEmpty">
            <div class="mb-8" id="ssl-expiry-section" style="display: none;">
                <x-heading type="h2" space=true>{{ __('notifications.ssl_expiry_notifications') }}</x-heading>
                <div id="ssl-expiry-notifications"></div>
                <div class="mt-4 text-center" id="ssl-expiry-load-more-container" style="display: none;">
                    <x-primary-button @click="loadMoreNotifications('ssl_expiry')">{{ __('notifications.load_more') }}</x-primary-button>
                </div>
            </div>

            <div class="mb-8" id="domain-expiry-section" style="display: none;">
                <x-heading type="h2" space=true>{{ __('notifications.domain_expiry_notifications') }}</x-heading>
                <div id="domain-expiry-notifications"></div>
                <div class="mt-4 text-center" id="domain-expiry-load-more-container" style="display: none;">
                    <x-primary-button @click="loadMoreNotifications('domain_expiry')">{{ __('notifications.load_more') }}</x-primary-button>
                </div>
            </div>

            <div class="mb-8" id="status-change-section" style="display: none;">
                <x-heading type="h2" space=true>{{ __('notifications.status_change_notifications') }}</x-heading>
                <div id="status-change-notifications"></div>
                <div class="mt-4 text-center" id="status-change-load-more-container" style="display: none;">
                    <x-primary-button @click="loadMoreNotifications('status_change')">{{ __('notifications.load_more') }}</x-primary-button>
                </div>
            </div>

            <div class="mb-8" id="delivery-history-section" style="display: none;">
                <x-heading type="h2" space=true>{{ __('notifications.delivery_history.heading') }}</x-heading>
                <div id="delivery-history-notifications"></div>
                <div class="mt-4 text-center" id="delivery-history-load-more-container" style="display: none;">
                    <x-primary-button @click="loadMoreNotifications('delivery_history')">{{ __('notifications.load_more') }}</x-primary-button>
                </div>
            </div>
        </div>
    </x-main>
</x-app-layout>
