@props(['services', 'pagination', 'total'])

<section id="dashboard-service-list" data-services='@json($services)' class="min-w-0 rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="signal-room-services-heading">
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6">
        <div>
            <h3 id="signal-room-services-heading" class="text-base font-extrabold text-gray-950 dark:text-white">{{ __('dashboard.signal_room.service_landscape') }}</h3>
            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">{{ $total }} {{ __('dashboard.signal_room.active_services') }}</p>
        </div>
        <span class="hidden rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-300 sm:inline-flex">{{ __('dashboard.signal_room.target') }}</span>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach (collect($services)->groupBy('group')->sortKeys() as $groupName => $groupServices)
            <div class="px-5 py-3 sm:px-6">
                <p class="mb-2 text-[11px] font-black uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ $groupName }}</p>
                <div class="space-y-2">
                    @foreach ($groupServices as $service)
                        @php
                            $serviceTone = match ($service['status']) {
                                'down' => ['dot' => 'bg-red-500', 'text' => 'text-red-700 dark:text-red-300'],
                                'unknown' => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-300'],
                                'maintenance' => ['dot' => 'bg-purple-500', 'text' => 'text-purple-700 dark:text-purple-300'],
                                'paused' => ['dot' => 'bg-gray-400', 'text' => 'text-gray-600 dark:text-gray-300'],
                                default => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-300'],
                            };
                        @endphp
                        <button type="button" data-signal-service="{{ $service['id'] }}" @click="selectService(@js($service['id']))" x-show="serviceVisible(@js($service))" :class="selectedServiceId === @js($service['id']) ? 'ring-2 ring-purple-500 ring-offset-1 dark:ring-offset-gray-800' : ''" class="group flex w-full items-center gap-3 rounded-2xl border border-gray-100 bg-gray-50 px-3.5 py-3 text-start transition hover:border-purple-200 hover:bg-purple-50/50 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800/70 dark:hover:border-purple-900 dark:hover:bg-purple-950/20">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $serviceTone['dot'] }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-extrabold text-gray-900 dark:text-white">{{ $service['name'] }}</span>
                                <span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">{{ $service['target'] }}</span>
                            </span>
                            <span class="hidden shrink-0 text-end sm:block"><span class="block text-xs font-bold {{ $serviceTone['text'] }}">{{ $service['statusLabel'] }}</span><span class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500">{{ $service['lastCheck'] }}</span></span>
                            <svg class="h-4 w-4 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @if ($pagination && $pagination['last_page'] > 1)
        <div id="dashboard-service-pagination" class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>{{ __('search.table.showing') }} {{ $pagination['from'] ?? 0 }} {{ __('search.table.to') }} {{ $pagination['to'] ?? 0 }} {{ __('search.table.of') }} {{ $pagination['total'] }} {{ __('search.table.entries') }}</p>
            <nav class="flex items-center gap-1.5" aria-label="{{ __('search.table.pagination') }}">
            @if ($pagination['current_page'] > 1)
                <a href="{{ request()->fullUrlWithQuery(['service_page' => $pagination['current_page'] - 1]) }}" @click.prevent="loadPage($el.href)" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-700 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-purple-500 dark:hover:bg-purple-950/30 dark:hover:text-purple-200">&laquo; {{ __('pagination.previous') }}</a>
            @endif
            <span class="inline-flex items-center rounded-lg bg-purple-700 px-3 py-2 font-bold text-white dark:bg-purple-600">{{ $pagination['current_page'] }} / {{ $pagination['last_page'] }}</span>
            @if ($pagination['current_page'] < $pagination['last_page'])
                <a href="{{ request()->fullUrlWithQuery(['service_page' => $pagination['current_page'] + 1]) }}" @click.prevent="loadPage($el.href)" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-700 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-purple-500 dark:hover:bg-purple-950/30 dark:hover:text-purple-200">{{ __('pagination.next') }} &raquo;</a>
            @endif
            </nav>
        </div>
    @endif
</section>
