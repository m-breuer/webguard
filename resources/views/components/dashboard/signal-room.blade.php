@props([
    'services',
    'pagination' => null,
    'summary',
    'overallState',
    'canCreateMonitoring' => false,
])

@php
    $serviceGroups = collect($services)->groupBy('group')->sortKeys();
    $initialServiceId = collect($services)->first()['id'] ?? null;
    $attentionCount = $summary['down'] + $summary['unknown'];
    $stateTone = match ($overallState) {
        'degraded' => ['dot' => 'bg-red-500', 'text' => 'text-red-700 dark:text-red-300', 'surface' => 'bg-red-50 dark:bg-red-950/20', 'border' => 'border-red-200 dark:border-red-900/60'],
        'attention' => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-300', 'surface' => 'bg-amber-50 dark:bg-amber-950/20', 'border' => 'border-amber-200 dark:border-amber-900/60'],
        default => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-300', 'surface' => 'bg-emerald-50 dark:bg-emerald-950/20', 'border' => 'border-emerald-200 dark:border-emerald-900/60'],
    };
@endphp

<section
    x-data="signalRoom({ services: @js($services), initialServiceId: @js($initialServiceId) })"
    data-signal-room
    class="space-y-5"
>
    <div class="flex flex-col gap-5 rounded-3xl border border-purple-100 bg-white p-6 shadow-sm dark:border-purple-900/50 dark:bg-gray-800 sm:p-7 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border {{ $stateTone['border'] }} {{ $stateTone['surface'] }} px-3 py-1.5 text-xs font-bold uppercase tracking-[0.16em] {{ $stateTone['text'] }}">
                    <span class="h-2 w-2 rounded-full {{ $stateTone['dot'] }}"></span>
                    {{ __('dashboard.signal_room.system_signal') }}
                </span>
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">{{ __('dashboard.signal_room.updated') }}</span>
            </div>
            <h2 class="mt-4 text-3xl font-black tracking-tight text-gray-950 dark:text-white sm:text-4xl">{{ __('dashboard.signal_room.heading') }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                @if ($attentionCount > 0)
                    {{ trans_choice('dashboard.signal_room.attention_summary', $attentionCount, ['count' => $attentionCount]) }}
                @else
                    {{ __('dashboard.signal_room.all_under_control') }}
                @endif
            </p>
        </div>

        @if ($canCreateMonitoring)
            <a
                href="{{ route('monitorings.create') }}"
                data-form-modal-trigger
                data-form-modal-name="monitoring-form-modal"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:bg-purple-600 dark:hover:bg-purple-500"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14" /></svg>
                {{ __('dashboard.signal_room.add_service') }}
            </a>
        @endif
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <label class="relative block min-w-0 flex-1 sm:max-w-md">
            <span class="sr-only">{{ __('dashboard.signal_room.search_placeholder') }}</span>
            <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m16.5 16.5 4 4" /></svg>
            <input x-model="query" type="search" class="w-full rounded-xl border-gray-200 bg-white py-3 ps-10 pe-4 text-sm shadow-sm placeholder:text-gray-400 focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="{{ __('dashboard.signal_room.search_placeholder') }}">
        </label>

        <div class="-mx-1 -my-2 flex gap-2 overflow-x-auto px-1 py-2" role="tablist" aria-label="{{ __('dashboard.signal_room.filters.all') }}">
            @foreach (['all', 'attention', 'maintenance', 'paused'] as $filter)
                <button
                    type="button"
                    data-signal-filter="{{ $filter }}"
                    role="tab"
                    :aria-selected="activeFilter === '{{ $filter }}'"
                    @click="activeFilter = '{{ $filter }}'"
                    :class="activeFilter === '{{ $filter }}' ? 'border-purple-700 bg-purple-700 text-white dark:bg-purple-600' : 'border-gray-200 bg-white text-gray-600 hover:border-purple-200 hover:text-purple-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                    class="whitespace-nowrap rounded-xl border px-3.5 py-2.5 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-950"
                >{{ __('dashboard.signal_room.filters.' . $filter) }}</button>
            @endforeach
        </div>
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,24rem)]">
        <div
            x-data="serviceMapLoader()"
            data-service-map-loader
            data-services='@json($services)'
            data-endpoint="{{ route('dashboard') }}"
            class="min-w-0"
        >
        <section id="dashboard-service-list" data-services='@json($services)' class="min-w-0 rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="signal-room-services-heading">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6">
                <div>
                    <h3 id="signal-room-services-heading" class="text-base font-extrabold text-gray-950 dark:text-white">{{ __('dashboard.signal_room.service_landscape') }}</h3>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">{{ $summary['total'] }} {{ __('dashboard.signal_room.active_services') }}</p>
                </div>
                <span class="hidden rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-300 sm:inline-flex">{{ __('dashboard.signal_room.target') }}</span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($serviceGroups as $groupName => $groupServices)
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
                                <button
                                    type="button"
                                    data-signal-service="{{ $service['id'] }}"
                                    @click="selectService(@js($service['id']))"
                                    @class([
                                        'group flex w-full items-center gap-3 rounded-2xl border px-3.5 py-3 text-start transition focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                                        'border-gray-100 bg-gray-50 hover:border-purple-200 hover:bg-purple-50/50 dark:border-gray-700 dark:bg-gray-800/70 dark:hover:border-purple-900 dark:hover:bg-purple-950/20' => $service['status'] === 'up',
                                        'border-red-100 bg-red-50/50 hover:border-red-200 dark:border-red-900/50 dark:bg-red-950/10' => $service['status'] === 'down',
                                        'border-amber-100 bg-amber-50/50 hover:border-amber-200 dark:border-amber-900/50 dark:bg-amber-950/10' => $service['status'] === 'unknown',
                                        'border-purple-100 bg-purple-50/50 hover:border-purple-200 dark:border-purple-900/50 dark:bg-purple-950/10' => in_array($service['status'], ['maintenance', 'paused'], true),
                                    ])
                                    x-show="serviceVisible(@js($service))"
                                    :class="selectedServiceId === @js($service['id']) ? 'ring-2 ring-purple-500 ring-offset-1 dark:ring-offset-gray-800' : ''"
                                >
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $serviceTone['dot'] }}"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-extrabold text-gray-900 dark:text-white">{{ $service['name'] }}</span>
                                        <span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">{{ $service['target'] }}</span>
                                    </span>
                                    <span class="hidden shrink-0 text-end sm:block">
                                        <span class="block text-xs font-bold {{ $serviceTone['text'] }}">{{ $service['statusLabel'] }}</span>
                                        <span class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500">{{ $service['lastCheck'] }}</span>
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-purple-600 dark:text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @if ($pagination)
            <x-pagination id="dashboard-service-pagination" :paginator="$pagination" page-param="service_page" :async="true" class="border-t border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6" />
        @endif
        </section>
        </div>

        <aside class="hidden lg:block">
            <x-dashboard.signal-room-detail />
        </aside>
    </div>

    <div data-signal-mobile-sheet x-show="mobileDetailOpen" x-cloak class="lg:hidden">
        <div class="fixed inset-0 z-40 bg-gray-950/35" aria-hidden="true" @click="closeDetail()"></div>
        <div data-signal-mobile-detail class="fixed inset-x-0 bottom-0 z-50 max-h-[88vh] overflow-y-auto rounded-t-3xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800" role="dialog" aria-modal="true" aria-label="{{ __('dashboard.signal_room.heading') }}">
            <x-dashboard.signal-room-detail mobile />
        </div>
    </div>
</section>
