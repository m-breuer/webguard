<x-app-layout>
    <x-slot name="header">
        <div>
            <x-heading type="h1">{{ $statusPage->name }}</x-heading>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                    {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                </x-badge>
                @if ($statusPage->is_public)
                    <a href="{{ route('public-status-pages.show', $statusPage) }}" target="_blank"
                        class="break-all hover:text-gray-700 dark:hover:text-white">
                        {{ route('public-status-pages.show', $statusPage) }}
                    </a>
                @endif
            </div>
        </div>

        <div class="ml-auto flex flex-wrap gap-2">
            @if (!Auth::user()->isDemo())
                <x-secondary-button :href="route('status-pages.edit', $statusPage)">
                    {{ __('button.edit') }}
                </x-secondary-button>
                <form method="POST" action="{{ route('status-pages.destroy', $statusPage) }}"
                    data-confirm-message="{{ __('status_page.actions.delete_confirmation') }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>
                        {{ __('button.delete') }}
                    </x-danger-button>
                </form>
            @endif
            <x-secondary-button :href="route('status-pages.index')">
                {{ __('button.back') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <x-main>
        <div class="space-y-4">
            @if ($statusPage->description)
                <x-container>
                    <x-paragraph>{{ $statusPage->description }}</x-paragraph>
                </x-container>
            @endif

            @foreach ($statusPage->components as $statusPageComponent)
                <x-container>
                    <x-heading type="h2">{{ $statusPageComponent->name }}</x-heading>
                    @if ($statusPageComponent->description)
                        <x-paragraph space="true">{{ $statusPageComponent->description }}</x-paragraph>
                    @endif

                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($statusPageComponent->monitorings as $monitoring)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div>
                                    <a href="{{ route('monitorings.show', $monitoring) }}"
                                        class="font-medium text-gray-900 hover:text-purple-600 dark:text-gray-100 dark:hover:text-purple-300">
                                        {{ $monitoring->name }}
                                    </a>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('monitoring.types.' . $monitoring->type->value) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                        @if ($statusPageComponent->source_type === \App\Enums\StatusPageComponentSource::MONITORING_GROUP)
                            @foreach ($statusPageComponent->monitoringGroup?->monitorings ?? [] as $monitoring)
                                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                    <div>
                                        <a href="{{ route('monitorings.show', $monitoring) }}"
                                            class="font-medium text-gray-900 hover:text-purple-600 dark:text-gray-100 dark:hover:text-purple-300">
                                            {{ $monitoring->name }}
                                        </a>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('monitoring.types.' . $monitoring->type->value) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </x-container>
            @endforeach

            <x-container>
                <x-heading type="h2">{{ __('status_page.incident_updates.heading') }}</x-heading>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('status_page.incident_updates.description') }}
                </p>

                @if ($incidents->isEmpty())
                    <p class="mt-4 text-gray-500 dark:text-gray-400">
                        {{ __('monitoring.detail.incidents.no_incidents') }}
                    </p>
                @else
                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($incidents as $incident)
                            <div class="space-y-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $incident->monitoring->name }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('monitoring.detail.incidents.incident.down_at') }}:
                                            {{ $incident->down_at->toDayDateTimeString() }}
                                            @if ($incident->up_at)
                                                - {{ __('monitoring.detail.incidents.incident.up_at') }}
                                                {{ $incident->up_at->toDayDateTimeString() }}
                                            @endif
                                        </p>
                                    </div>
                                    <x-badge :type="$incident->up_at ? 'success' : 'danger'">
                                        {{ $incident->up_at ? __('monitoring.public_label.resolved') : __('monitoring.public_label.ongoing') }}
                                    </x-badge>
                                </div>

                                @if ($incident->updates->isNotEmpty())
                                    <div class="space-y-3">
                                        @foreach ($incident->updates as $incidentUpdate)
                                            <div class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <x-badge :type="$incidentUpdate->status->badgeType()">
                                                        {{ __('status_page.incident_updates.statuses.' . $incidentUpdate->status->value) }}
                                                    </x-badge>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $incidentUpdate->created_at->toDayDateTimeString() }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $incidentUpdate->message }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!Auth::user()->isDemo())
                                    <form method="POST"
                                        action="{{ route('status-pages.incident-updates.store', [$statusPage, $incident]) }}"
                                        class="space-y-3 rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                        @csrf
                                        <div>
                                            <x-input-label for="incident-update-status-{{ $incident->id }}"
                                                :value="__('status_page.incident_updates.status')" />
                                            <x-select-input id="incident-update-status-{{ $incident->id }}" name="status"
                                                class="mt-1 w-full">
                                                @foreach (\App\Enums\IncidentUpdateStatus::cases() as $incidentUpdateStatus)
                                                    <option value="{{ $incidentUpdateStatus->value }}"
                                                        @selected(old('status') === $incidentUpdateStatus->value)>
                                                        {{ __('status_page.incident_updates.statuses.' . $incidentUpdateStatus->value) }}
                                                    </option>
                                                @endforeach
                                            </x-select-input>
                                            <x-input-error :messages="$errors->get('status')" />
                                        </div>
                                        <div>
                                            <x-input-label for="incident-update-message-{{ $incident->id }}"
                                                :value="__('status_page.incident_updates.message')" />
                                            <x-textarea id="incident-update-message-{{ $incident->id }}" name="message"
                                                rows="3">{{ old('message') }}</x-textarea>
                                            <x-input-error :messages="$errors->get('message')" />
                                        </div>
                                        <x-primary-button>{{ __('status_page.incident_updates.add') }}</x-primary-button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-container>
        </div>
    </x-main>
</x-app-layout>
