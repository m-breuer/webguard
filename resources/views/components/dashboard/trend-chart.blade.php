@props([
    'trend',
])

@if (collect($trend)->contains('has_data', true))
    @php
        $trendPoints = collect($trend)->map(function (array $point, int $index) use ($trend): string {
            $x = 20 + ($index * 560 / max(1, count($trend) - 1));
            $uptime = $point['has_data'] ? (float) $point['uptime_percentage'] : 97;
            $y = 140 - ((max(97, min(100, $uptime)) - 97) / 3 * 100);

            return $x . ',' . $y;
        })->implode(' ');
    @endphp

    <div class="px-5 pb-5 pt-6 sm:px-6" aria-label="{{ __('dashboard.trend.heading') }}">
        <svg viewBox="0 0 600 170" class="h-44 w-full overflow-visible" role="img" aria-label="{{ __('dashboard.trend.heading') }}">
            @foreach ([97, 98, 99, 100] as $gridValue)
                @php $gridY = 140 - (($gridValue - 97) / 3 * 100); @endphp
                <line x1="20" y1="{{ $gridY }}" x2="580" y2="{{ $gridY }}" stroke="currentColor" stroke-dasharray="3 5" class="text-gray-200 dark:text-gray-700" />
                <text x="0" y="{{ $gridY + 4 }}" class="fill-gray-400 text-[10px]">{{ $gridValue }}%</text>
            @endforeach
            <polyline points="{{ $trendPoints }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600 dark:text-purple-300" />
            @foreach (collect($trend) as $index => $point)
                @php
                    $x = 20 + ($index * 560 / max(1, count($trend) - 1));
                    $uptime = $point['has_data'] ? (float) $point['uptime_percentage'] : 97;
                    $y = 140 - ((max(97, min(100, $uptime)) - 97) / 3 * 100);
                @endphp
                <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="currentColor" class="text-purple-600 dark:text-purple-300" />
                <text x="{{ $x }}" y="160" text-anchor="middle" class="fill-gray-400 text-[10px]">{{ $point['label'] }}</text>
            @endforeach
        </svg>
    </div>
@else
    <p class="m-5 rounded-2xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('dashboard.trend.no_data') }}</p>
@endif
