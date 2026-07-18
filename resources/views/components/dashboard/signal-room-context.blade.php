@props([
    'attentionItems',
    'maintenanceMonitorings',
    'failedDeliveryCount',
    'statusPages',
    'canManageMaintenance' => false,
])

<section class="space-y-4" aria-labelledby="signal-room-context-heading">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-purple-600 dark:text-purple-300">{{ __('dashboard.signal_room.context.kicker') }}</p>
            <h2 id="signal-room-context-heading" class="mt-1 text-xl font-black tracking-tight text-gray-950 dark:text-white">{{ __('dashboard.signal_room.context.heading') }}</h2>
        </div>
        <a href="{{ route('incidents.analytics') }}" class="text-sm font-bold text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-200">{{ __('dashboard.signal_room.context.open_workspace') }}</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(18rem,0.85fr)_minmax(18rem,0.85fr)]">
        <article class="rounded-3xl border border-red-100 bg-white p-5 shadow-sm dark:border-red-900/50 dark:bg-gray-800 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-gray-950 dark:text-white">{{ __('dashboard.signal_room.context.attention_heading') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.context.attention_description') }}</p>
                </div>
                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-red-50 px-2 text-sm font-black text-red-700 dark:bg-red-950/30 dark:text-red-300">{{ $attentionItems->count() }}</span>
            </div>
            @if ($attentionItems->isEmpty())
                <p class="mt-5 rounded-2xl border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.signal_room.context.attention_empty') }}</p>
            @else
                <div class="mt-4 space-y-2">
                    @foreach ($attentionItems->take(3) as $item)
                        @php
                            $itemIncident = $item['monitoring']?->latestIncident;
                            $itemStatusPage = $item['statusPage'] ?? null;
                            $itemHref = $item['type'] === 'delivery'
                                ? route('notifications.index')
                                : ($itemStatusPage && $itemIncident
                                    ? route('status-pages.show', ['statusPage' => $itemStatusPage, 'incident_id' => $itemIncident->id]) . '#incident-workbench-' . $itemIncident->id
                                    : route('monitorings.show', $item['monitoring']));
                            $itemText = match ($item['type']) {
                                'incident' => __('dashboard.attention.incident', ['name' => $item['monitoring']->name]),
                                'down' => __('dashboard.attention.down', ['name' => $item['monitoring']->name]),
                                'unknown' => __('dashboard.attention.unknown', ['name' => $item['monitoring']->name]),
                                'stale' => __('dashboard.attention.stale', ['name' => $item['monitoring']->name]),
                                default => __('dashboard.attention.delivery', ['count' => $item['count']]),
                            };
                        @endphp
                        <a href="{{ $itemHref }}" class="flex items-center gap-3 rounded-2xl border border-gray-100 px-3 py-3 text-sm transition hover:border-red-200 hover:bg-red-50/50 dark:border-gray-700 dark:hover:border-red-900 dark:hover:bg-red-950/10">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $item['type'] === 'stale' || $item['type'] === 'unknown' ? 'bg-amber-500' : 'bg-red-500' }}"></span>
                            <span class="min-w-0 flex-1 truncate font-bold text-gray-800 dark:text-gray-100">{{ $itemText }}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="rounded-3xl border border-amber-100 bg-white p-5 shadow-sm dark:border-amber-900/50 dark:bg-gray-800 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-gray-950 dark:text-white">{{ __('dashboard.signal_room.context.maintenance_heading') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.context.maintenance_description') }}</p>
                </div>
                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-amber-50 px-2 text-sm font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">{{ $maintenanceMonitorings->count() }}</span>
            </div>
            @if ($maintenanceMonitorings->isEmpty())
                <p class="mt-5 rounded-2xl border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.signal_room.context.maintenance_empty') }}</p>
            @else
                <div class="mt-4 space-y-2">
                    @foreach ($maintenanceMonitorings->take(2) as $monitoring)
                        <a href="{{ route('maintenance.index') }}" class="block rounded-2xl border border-gray-100 px-3 py-3 transition hover:border-amber-200 hover:bg-amber-50/50 dark:border-gray-700 dark:hover:border-amber-900 dark:hover:bg-amber-950/10">
                            <span class="block truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ $monitoring->name }}</span>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $monitoring->maintenance_from->locale(app()->getLocale())->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('maintenance.index') }}" class="mt-4 inline-flex text-sm font-bold text-purple-700 hover:text-purple-900 dark:text-purple-300">{{ $canManageMaintenance ? __('dashboard.signal_room.context.open_maintenance') : __('dashboard.signal_room.context.view_maintenance') }}</a>
        </article>

        <article class="rounded-3xl border border-blue-100 bg-white p-5 shadow-sm dark:border-blue-900/50 dark:bg-gray-800 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-gray-950 dark:text-white">{{ __('dashboard.signal_room.context.surfaces_heading') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.signal_room.context.surfaces_description') }}</p>
                </div>
                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-blue-50 px-2 text-sm font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $statusPages->count() }}</span>
            </div>
            @if ($statusPages->isEmpty())
                <p class="mt-5 rounded-2xl border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.signal_room.context.surfaces_empty') }}</p>
            @else
                <div class="mt-4 space-y-2">
                    @foreach ($statusPages->take(2) as $statusPage)
                        <a href="{{ route('status-pages.show', $statusPage) }}" class="flex items-center gap-3 rounded-2xl border border-gray-100 px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50/50 dark:border-gray-700 dark:hover:border-blue-900 dark:hover:bg-blue-950/10">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $statusPage->is_public ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            <span class="min-w-0 flex-1 truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ $statusPage->name }}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                        </a>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('status-pages.index') }}" class="mt-4 inline-flex text-sm font-bold text-purple-700 hover:text-purple-900 dark:text-purple-300">{{ __('dashboard.signal_room.context.open_status_pages') }}</a>
        </article>
    </div>

    @if ($failedDeliveryCount > 0)
        <a href="{{ route('notifications.index') }}" class="flex flex-col gap-2 rounded-2xl border border-red-100 bg-red-50/60 px-4 py-3 text-sm transition hover:border-red-200 sm:flex-row sm:items-center sm:justify-between dark:border-red-900/50 dark:bg-red-950/10">
            <span class="font-bold text-red-800 dark:text-red-200">{{ __('dashboard.signal_room.context.delivery_warning', ['count' => $failedDeliveryCount]) }}</span>
            <span class="font-bold text-purple-700 dark:text-purple-300">{{ __('dashboard.signal_room.context.open_notifications') }}</span>
        </a>
    @endif
</section>
