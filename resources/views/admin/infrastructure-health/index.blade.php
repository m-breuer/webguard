@php
    use Illuminate\Support\Str;

    $badgeType = static fn (string $status): string => match ($status) {
        'healthy' => 'success',
        'warning' => 'warning',
        'critical' => 'danger',
        default => 'info',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading type="h1">
            {{ __('admin.infrastructure_health.title') }}
        </x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <x-heading type="h2">{{ __('admin.infrastructure_health.summary.heading') }}</x-heading>
                <x-paragraph class="mt-1 text-gray-500 dark:text-gray-400">
                    {{ __('admin.infrastructure_health.summary.generated_at', ['timestamp' => \Illuminate\Support\Facades\Date::parse($report['generated_at'])->toDayDateTimeString()]) }}
                </x-paragraph>
            </div>
            <x-badge :type="$badgeType($report['status'])">
                {{ __('admin.infrastructure_health.statuses.' . $report['status']) }}
            </x-badge>
        </x-container>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($report['checks'] as $checkKey => $check)
                <x-container class="h-full">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <x-heading type="h2">
                            {{ __('admin.infrastructure_health.checks.' . $checkKey . '.title') }}
                        </x-heading>
                        <x-badge :type="$badgeType($check['status'])">
                            {{ __('admin.infrastructure_health.statuses.' . $check['status']) }}
                        </x-badge>
                    </div>

                    <x-paragraph class="mt-3 text-gray-600 dark:text-gray-300">
                        {{ __($check['message'], $check['meta']) }}
                    </x-paragraph>

                    @if (! empty($check['meta']))
                        <dl class="mt-4 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                            @foreach ($check['meta'] as $metaKey => $metaValue)
                                @php
                                    $labelKey = 'admin.infrastructure_health.meta.' . $metaKey;
                                    $label = __($labelKey);
                                    if ($label === $labelKey) {
                                        $label = Str::headline((string) $metaKey);
                                    }
                                @endphp
                                <div>
                                    <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                    <dd class="break-all text-gray-900 dark:text-gray-100">
                                        {{ is_scalar($metaValue) || $metaValue === null ? ($metaValue ?? __('admin.infrastructure_health.meta.empty')) : json_encode($metaValue) }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </x-container>
            @endforeach
        </div>
    </x-main>
</x-app-layout>
