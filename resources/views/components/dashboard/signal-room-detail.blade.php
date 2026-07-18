@props(['mobile' => false])

<template x-for="service in services" :key="service.id">
    <div data-signal-detail x-show="selectedServiceId === service.id" x-cloak class="p-5 sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-purple-600 dark:text-purple-300">{{ __('dashboard.signal_room.tabs.signal') }}</p>
                <h3 class="mt-2 truncate text-xl font-black text-gray-950 dark:text-white" x-text="service.name"></h3>
                <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400" x-text="service.group"></p>
            </div>
            @if ($mobile)
                <button type="button" @click="closeDetail()" class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="{{ __('button.cancel') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            @endif
        </div>

        <div class="mt-5 flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-700">
            @foreach (['signal', 'checks', 'incidents', 'history'] as $tab)
                <span class="flex-1 rounded-lg px-2 py-2 text-center text-[11px] font-bold {{ $tab === 'signal' ? 'bg-white text-purple-700 shadow-sm dark:bg-gray-600 dark:text-purple-200' : 'text-gray-500 dark:text-gray-300' }}">{{ __('dashboard.signal_room.tabs.' . $tab) }}</span>
            @endforeach
        </div>

        <div class="mt-5 rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="flex items-center gap-3">
                <span class="h-3 w-3 rounded-full" :class="{ 'bg-emerald-500': service.status === 'up', 'bg-red-500': service.status === 'down', 'bg-amber-500': service.status === 'unknown', 'bg-purple-500': ['maintenance', 'paused'].includes(service.status) }"></span>
                <span class="text-sm font-extrabold text-gray-900 dark:text-white" x-text="service.statusLabel"></span>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                <div class="min-w-0">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.detail_target') }}</dt>
                    <dd class="mt-1 truncate font-bold text-gray-900 dark:text-white" x-text="service.target"></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.detail_last_check') }}</dt>
                    <dd class="mt-1 font-bold text-gray-900 dark:text-white" x-text="service.lastCheck"></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.detail_response_time') }}</dt>
                    <dd class="mt-1 font-bold text-gray-900 dark:text-white" x-text="service.responseTime"></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.tabs.incidents') }}</dt>
                    <dd class="mt-1 font-bold" :class="service.openIncident ? 'text-red-600 dark:text-red-300' : 'text-emerald-600 dark:text-emerald-300'" x-text="service.openIncident ? '{{ __('dashboard.signal_room.detail_open_incident') }}' : '{{ __('dashboard.signal_room.all_under_control') }}'"></dd>
                </div>
            </dl>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
            <a :href="service.href" class="inline-flex flex-1 items-center justify-center rounded-xl bg-purple-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:bg-purple-600 dark:hover:bg-purple-500">{{ __('dashboard.signal_room.full_details') }}</a>
            @if ($mobile)
                <button type="button" @click="closeDetail()" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-700 hover:border-purple-200 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:border-gray-700 dark:text-gray-200">{{ __('button.cancel') }}</button>
            @endif
        </div>
    </div>
</template>
