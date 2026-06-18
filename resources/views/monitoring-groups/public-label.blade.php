@php
    use App\Enums\MonitoringType;
@endphp

<x-public-layout>
    <x-slot name="head">
        <meta name="robots" content="noindex">
        <title>{{ __('monitoring_group.public_label.title', ['groupName' => $monitoringGroup->name]) }}</title>
    </x-slot>

    <x-slot name="header">
        <div>
            <x-heading>
                {{ $monitoringGroup->name }}
            </x-heading>
            @if ($monitoringGroup->description)
                <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-300">
                    {{ $monitoringGroup->description }}
                </p>
            @endif
        </div>
    </x-slot>

    <x-main>
        <div class="space-y-4">
            @if ($monitorings->isEmpty())
                <x-container class="text-center">
                    <x-heading type="h2">{{ __('monitoring_group.public_label.empty.title') }}</x-heading>
                    <x-paragraph space="true">{{ __('monitoring_group.public_label.empty.text') }}</x-paragraph>
                </x-container>
            @else
                @foreach ($monitorings as $monitoring)
                    @php
                        $statusSummary = $statusSummaries[$monitoring->id] ?? ['status' => 'unknown', 'badge' => 'warning', 'since' => null];
                        $displayTarget = $monitoring->type === MonitoringType::HEARTBEAT ? null : $monitoring->target;
                    @endphp
                    <x-container>
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-heading type="h2">{{ $monitoring->name }}</x-heading>
                                    <x-badge :type="$statusSummary['badge']">
                                        {{ mb_strtoupper($statusSummary['status']) }}
                                    </x-badge>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <x-badge type="info">{{ __('monitoring.types.' . $monitoring->type->value) }}</x-badge>
                                    @if ($displayTarget)
                                        <a href="{{ $displayTarget }}" target="_blank" title="{{ $monitoring->name }}"
                                            class="break-all hover:text-gray-700 dark:hover:text-white">
                                            {{ $displayTarget }}
                                        </a>
                                    @else
                                        <span>{{ __('monitoring.public_label.private_target') }}</span>
                                    @endif
                                    @if ($statusSummary['since'])
                                        <span>
                                            {{ __('monitoring.index.table.since') }}
                                            {{ \Illuminate\Support\Facades\Date::parse($statusSummary['since'])->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <x-secondary-button :href="route('public-label', $monitoring)">
                                {{ __('monitoring_group.actions.public_label') }}
                            </x-secondary-button>
                        </div>
                    </x-container>
                @endforeach
            @endif
        </div>
    </x-main>
</x-public-layout>
