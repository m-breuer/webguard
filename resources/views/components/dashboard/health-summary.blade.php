@props([
    'summary',
    'healthPercentage',
    'healthCircumference',
    'healthOffset',
    'stateTone',
    'overallState',
])

@php
    $metrics = [
        ['key' => 'healthy', 'value' => $summary['healthy'], 'tone' => 'bg-emerald-500', 'href' => route('monitorings.index')],
        ['key' => 'down', 'value' => $summary['down'], 'tone' => 'bg-red-500', 'href' => route('incidents.analytics')],
        ['key' => 'unknown', 'value' => $summary['unknown'], 'tone' => 'bg-amber-500', 'href' => route('monitorings.index', ['lifecycle' => 'active'])],
        ['key' => 'paused', 'value' => $summary['paused'], 'tone' => 'bg-gray-400', 'href' => route('monitorings.index', ['lifecycle' => 'paused'])],
        ['key' => 'maintenance', 'value' => $summary['maintenance'], 'tone' => 'bg-purple-500', 'href' => route('maintenance.index', ['maintenance_status' => 'active'])],
    ];
@endphp

<section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-8">
    <div class="flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-6">
            <div class="relative h-32 w-32 shrink-0 sm:h-36 sm:w-36">
                <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100" role="img" aria-label="{{ $healthPercentage }}% {{ __('dashboard.summary.healthy') }}">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="7" class="text-gray-100 dark:text-gray-700" />
                    <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-dasharray="{{ $healthCircumference }}" stroke-dashoffset="{{ $healthOffset }}" class="{{ $stateTone['ring'] }} transition-all duration-700" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ $healthPercentage }}%</span>
                    <span class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('dashboard.summary.healthy') }}</span>
                </div>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">{{ __('dashboard.summary.total') }} · {{ $summary['total'] }}</p>
                <x-heading type="h2" class="mt-2">{{ __('dashboard.state.' . $overallState . '.title') }}</x-heading>
                <p class="mt-2 max-w-lg text-base leading-7 text-gray-600 dark:text-gray-300">
                    @if ($overallState === 'degraded')
                        {{ trans_choice('dashboard.state.degraded.description', $summary['down'], ['count' => $summary['down']]) }}
                    @elseif ($overallState === 'attention')
                        {{ trans_choice('dashboard.state.attention.description', $summary['unknown'], ['count' => $summary['unknown']]) }}
                    @else
                        {{ __('dashboard.state.' . $overallState . '.description') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-2 divide-x divide-gray-200 border-t border-gray-200 pt-5 dark:divide-gray-700 dark:border-gray-700 sm:grid-cols-5">
        @foreach ($metrics as $metric)
            <x-dashboard.metric-link :href="$metric['href']" :value="$metric['value']" :label="__('dashboard.summary.' . $metric['key'])" :tone="$metric['tone']" />
        @endforeach
    </div>
</section>
