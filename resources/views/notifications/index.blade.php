<x-app-layout>
    @php
        $showReadEnabledQuery = request()->query();
        $showReadEnabledQuery['show_read'] = 1;

        $showReadDisabledQuery = request()->query();
        unset($showReadDisabledQuery['show_read']);

        $notificationSections = [
            [
                'type' => 'ssl_expiry',
                'sectionId' => 'ssl-expiry-section',
                'containerId' => 'ssl-expiry-notifications',
                'loadMoreContainerId' => 'ssl-expiry-load-more-container',
                'title' => __('notifications.ssl_expiry_notifications'),
                'description' => __('notifications.sections.ssl_expiry.description'),
                'accent' => 'bg-amber-500',
            ],
            [
                'type' => 'domain_expiry',
                'sectionId' => 'domain-expiry-section',
                'containerId' => 'domain-expiry-notifications',
                'loadMoreContainerId' => 'domain-expiry-load-more-container',
                'title' => __('notifications.domain_expiry_notifications'),
                'description' => __('notifications.sections.domain_expiry.description'),
                'accent' => 'bg-sky-500',
            ],
            [
                'type' => 'status_change',
                'sectionId' => 'status-change-section',
                'containerId' => 'status-change-notifications',
                'loadMoreContainerId' => 'status-change-load-more-container',
                'title' => __('notifications.status_change_notifications'),
                'description' => __('notifications.sections.status_change.description'),
                'accent' => 'bg-emerald-500',
            ],
            [
                'type' => 'delivery_history',
                'sectionId' => 'delivery-history-section',
                'containerId' => 'delivery-history-notifications',
                'loadMoreContainerId' => 'delivery-history-load-more-container',
                'title' => __('notifications.delivery_history.heading'),
                'description' => __('notifications.sections.delivery_history.description'),
                'accent' => 'bg-violet-500',
            ],
        ];
    @endphp

    <x-slot name="header">
        <div id="notification-command-center" class="grid w-full gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,30rem)] lg:items-center">
            <div class="flex min-w-0 items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-300/10 dark:text-emerald-300 dark:ring-emerald-300/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17a3 3 0 1 1-6 0" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <x-paragraph class="text-sm font-semibold uppercase tracking-[0.08em] text-emerald-700 dark:text-emerald-300">
                        {{ __('notifications.overview.eyebrow') }}
                    </x-paragraph>
                    <x-heading type="h1" class="mt-1 text-slate-950 dark:text-white">
                        {{ __('notifications.title') }}
                    </x-heading>
                    <x-paragraph class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300 sm:text-base">
                        {{ __('notifications.overview.description') }}
                    </x-paragraph>
                </div>
            </div>

            <div id="notification-action-panel" class="rounded-xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                    <div>
                        <x-paragraph class="px-1 text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">
                            {{ __('notifications.filters.heading') }}
                        </x-paragraph>
                        <nav class="mt-2 grid grid-cols-2 rounded-lg bg-slate-100 p-1 text-sm font-semibold dark:bg-slate-800" aria-label="{{ __('notifications.filters.heading') }}">
                            <a href="{{ route('notifications.index', $showReadDisabledQuery) }}"
                                class="{{ ! $showRead ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-950 dark:text-emerald-300 dark:ring-slate-700' : 'text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white' }} inline-flex min-h-10 items-center justify-center rounded-md px-3 text-center transition">
                                {{ __('notifications.filters.unread') }}
                            </a>
                            <a href="{{ route('notifications.index', $showReadEnabledQuery) }}"
                                class="{{ $showRead ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-950 dark:text-emerald-300 dark:ring-slate-700' : 'text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white' }} inline-flex min-h-10 items-center justify-center rounded-md px-3 text-center transition">
                                {{ __('notifications.filters.all') }}
                            </a>
                        </nav>
                    </div>

                    <form method="POST" action="{{ route('notifications.markAllAsRead') }}" class="sm:pb-1">
                        @csrf
                        <x-secondary-button type="submit" class="!w-full justify-center !border-slate-300 px-3 py-2 text-sm !normal-case !tracking-normal !text-slate-700 hover:!border-emerald-500 hover:!bg-emerald-50 hover:!text-emerald-700 dark:!border-slate-600 dark:!bg-slate-800 dark:!text-slate-100 dark:hover:!border-emerald-400 dark:hover:!bg-emerald-300/10 dark:hover:!text-emerald-300 sm:!w-auto">
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4 12 5 5L20 6" />
                            </svg>
                            {{ __('notifications.mark_all_as_read') }}
                        </x-secondary-button>
                    </form>
                </div>
            </div>
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
    }" x-init="syncLimitWithUrl(currentLimit); loadInitialNotifications()" class="space-y-6">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-3" aria-label="{{ __('notifications.overview.workflow_label') }}">
            @foreach (['triage', 'expiry', 'audit'] as $workflowItem)
                <div class="rounded-lg border border-slate-200 bg-white/75 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/50 sm:p-4">
                    <div class="flex items-center justify-between gap-3 sm:block">
                        <x-paragraph class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">
                            {{ __('notifications.overview.workflow.' . $workflowItem . '.label') }}
                        </x-paragraph>
                        <x-heading type="h3" class="text-right text-sm font-semibold text-slate-950 dark:text-white sm:mt-2 sm:text-left sm:text-base">
                            {{ __('notifications.overview.workflow.' . $workflowItem . '.title') }}
                        </x-heading>
                    </div>
                    <x-paragraph class="mt-1 hidden text-sm leading-6 text-slate-600 dark:text-slate-300 sm:block">
                        {{ __('notifications.overview.workflow.' . $workflowItem . '.description') }}
                    </x-paragraph>
                </div>
            @endforeach
        </div>

        <x-container id="notifications-loading-state" x-cloak x-show="isLoading" class="border border-slate-200 bg-white/90 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <div class="flex items-center gap-4">
                <x-loading-indicator />
                <div>
                    <x-heading type="h3" class="text-base font-semibold text-slate-950 dark:text-white">
                        {{ __('notifications.loading.title') }}
                    </x-heading>
                    <x-paragraph class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        {{ __('notifications.loading.description') }}
                    </x-paragraph>
                </div>
            </div>
        </x-container>

        <x-container id="notifications-empty-state" x-cloak x-show="!isLoading && isEmpty" class="border border-emerald-200 bg-emerald-50/70 text-center shadow-sm dark:border-emerald-300/20 dark:bg-emerald-300/10">
            <div class="mx-auto flex max-w-2xl flex-col items-center">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-200 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-300/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4 12 5 5L20 6" />
                    </svg>
                </span>
                <x-heading type="h2" class="mt-4 text-xl font-semibold text-slate-950 dark:text-white">
                    {{ __('notifications.no_notifications') }}
                </x-heading>
                <x-paragraph class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('notifications.empty_state.description') }}
                </x-paragraph>
            </div>
        </x-container>

        <div x-cloak x-show="!isLoading && !isEmpty" class="space-y-8 sm:space-y-10">
            @foreach ($notificationSections as $section)
                <section class="notification-section" id="{{ $section['sectionId'] }}" data-notification-section="{{ $section['type'] }}" style="display: none;">
                    <div class="mb-4 flex flex-col gap-3 border-b border-slate-200 pb-4 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="{{ $section['accent'] }} mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white shadow-sm">
                                @switch($section['type'])
                                    @case('domain_expiry')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3c2.5 2.4 2.5 15.6 0 18" />
                                        </svg>
                                        @break
                                    @case('status_change')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h4l3-6 4 12 2-6h3" />
                                        </svg>
                                        @break
                                    @case('delivery_history')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M7 12h10M9 17h6" />
                                        </svg>
                                        @break
                                    @default
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V8a4 4 0 1 1 8 0v3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11h10v9H7z" />
                                        </svg>
                                @endswitch
                            </span>
                            <div class="min-w-0">
                                <x-heading type="h2" class="text-xl font-semibold text-slate-950 dark:text-white">
                                    {{ $section['title'] }}
                                </x-heading>
                                <x-paragraph class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {{ $section['description'] }}
                                </x-paragraph>
                            </div>
                        </div>
                    </div>

                    <div id="{{ $section['containerId'] }}" class="space-y-3"></div>

                    <div class="mt-4 text-center" id="{{ $section['loadMoreContainerId'] }}" style="display: none;">
                        <x-primary-button @click="loadMoreNotifications('{{ $section['type'] }}')" class="!w-full justify-center !bg-emerald-600 px-4 py-2 text-sm !normal-case !tracking-normal hover:!bg-emerald-700 focus:!bg-emerald-700 focus:!ring-emerald-500 dark:!bg-emerald-500 dark:hover:!bg-emerald-400 sm:!w-auto">
                            {{ __('notifications.load_more') }}
                        </x-primary-button>
                    </div>
                </section>
            @endforeach
        </div>
    </x-main>
</x-app-layout>
