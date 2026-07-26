<x-app-layout>
    <x-slot name="header">
        <div data-status-page-detail-header>
            <a href="{{ route('status-pages.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-200">
                <span aria-hidden="true">←</span>
                {{ __('status_page.detail.back') }}
            </a>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <x-heading type="h1">{{ $statusPage->name }}</x-heading>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <x-badge :type="$statusPage->is_public ? 'success' : 'warning'">
                    {{ $statusPage->is_public ? __('status_page.state.public') : __('status_page.state.private') }}
                </x-badge>
                        <x-badge type="info">
                            {{ $statusPage->is_public ? __('status_page.detail.published') : __('status_page.detail.draft') }}
                        </x-badge>
                @if ($statusPage->is_public)
                    <a href="{{ route('public-status-pages.show', $statusPage) }}" target="_blank"
                        class="break-all hover:text-gray-700 dark:hover:text-white">
                        {{ route('public-status-pages.show', $statusPage) }}
                    </a>
                @endif
                    </div>
                </div>

                <div x-data="formModalLoader()" data-form-modal-error="{{ __('app.messages.form_modal_load_error') }}" class="flex flex-wrap gap-2">
                    <x-secondary-button :href="route('incidents.analytics')" :icon-only="true"
                        title="{{ __('incidents.analytics.link') }}" aria-label="{{ __('incidents.analytics.link') }}">
                        <x-icon name="chart" class="h-4 w-4" />
                    </x-secondary-button>
                    @if (!Auth::user()->isDemo())
                        <x-secondary-button :href="route('status-pages.edit', $statusPage)"
                            data-form-modal-trigger data-form-modal-name="status-page-form-modal" :icon-only="true"
                            title="{{ __('button.edit') }}" aria-label="{{ __('button.edit') }}">
                            <x-icon name="pencil" class="h-4 w-4" />
                        </x-secondary-button>
                        <form method="POST" action="{{ route('status-pages.destroy', $statusPage) }}"
                            data-confirm-message="{{ __('status_page.actions.delete_confirmation') }}">
                            @csrf
                            @method('DELETE')
                            <x-danger-button :icon-only="true" title="{{ __('button.delete') }}" aria-label="{{ __('button.delete') }}">
                                <x-icon name="trash" class="h-4 w-4" />
                            </x-danger-button>
                        </form>
                    @endif
                    <x-form-modal name="status-page-form-modal" title="{{ __('status_page.title') }}"
                        description="{{ __('status_page.form.components') }}" max-width="5xl">
                        <div class="p-6" x-ref="content">
                            <x-loading-indicator x-show="loading" x-cloak :show-label="false" class="justify-center" />
                            <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
                            <div x-html="content"></div>
                        </div>
                    </x-form-modal>
                </div>
            </div>
        </div>
    </x-slot>

    <x-main>
        <div data-status-page-detail-layout class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-4">
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
                <div class="flex flex-wrap items-center gap-2">
                    <x-heading type="h2">{{ __('status_page.incident_updates.heading') }}</x-heading>
                    <x-badge type="info">{{ __('status_page.incident_workbench.public_updates') }}</x-badge>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('status_page.incident_updates.description') }}
                </p>
                <form method="GET" action="{{ route('status-pages.show', $statusPage) }}" class="mt-4 grid gap-3 rounded-md border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-3">
                    <div>
                        <x-input-label for="follow-up-status-filter" :value="__('status_page.incident_follow_ups.filters.status')" />
                        <x-select-input id="follow-up-status-filter" name="follow_up_status" class="mt-1 w-full">
                            <option value="">{{ __('status_page.incident_follow_ups.filters.all') }}</option>
                            @foreach ($followUpStatuses as $followUpStatus)
                                <option value="{{ $followUpStatus->value }}" @selected($followUpFilters['status'] === $followUpStatus->value)>
                                    {{ __('status_page.incident_follow_ups.statuses.' . $followUpStatus->value) }}
                                </option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="follow-up-assignee-filter" :value="__('status_page.incident_follow_ups.filters.assignee')" />
                        <x-select-input id="follow-up-assignee-filter" name="follow_up_assignee" class="mt-1 w-full">
                            <option value="">{{ __('status_page.incident_follow_ups.filters.all') }}</option>
                            <option value="{{ $statusPage->user_id }}" @selected($followUpFilters['assignee'] === $statusPage->user_id)>{{ $statusPage->user->name }}</option>
                        </x-select-input>
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>{{ __('status_page.incident_follow_ups.filters.apply') }}</x-primary-button>
                    </div>
                </form>

                @if ($incidents->isEmpty())
                    <p class="mt-4 text-gray-500 dark:text-gray-400">
                        {{ __('monitoring.detail.incidents.no_incidents') }}
                    </p>
                @else
                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($incidents as $incident)
                                <div id="incident-{{ $incident->id }}" class="space-y-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $incident->monitoring->name }}
                                            </p>
                                            @if ($incident->severity)
                                                <x-badge :type="$incident->severity->badgeType()">
                                                    {{ __('incidents.severities.' . $incident->severity->value) }}
                                                </x-badge>
                                            @endif
                                            @if ($incident->customer_impact)
                                                <x-badge type="info">
                                                    {{ __('incidents.customer_impacts.' . $incident->customer_impact->value) }}
                                                </x-badge>
                                            @endif
                                        </div>
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

                                <details id="incident-workbench-{{ $incident->id }}" @if ($openIncidentId === $incident->id || $incident->up_at === null) open @endif class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-950/20">
                                    <summary class="cursor-pointer list-none">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-slate-100">
                                                    {{ __('status_page.incident_workbench.heading') }}
                                                </p>
                                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                                    {{ __('status_page.incident_workbench.description') }}
                                                </p>
                                            </div>
                                            <span class="text-sm font-medium text-purple-700 dark:text-purple-300">+</span>
                                        </div>
                                    </summary>
                                    <div class="mt-4 space-y-4">

                                @if (!Auth::user()->isDemo())
                                    <form method="POST"
                                        action="{{ route('status-pages.incident-metadata.update', [$statusPage, $incident]) }}"
                                        class="space-y-3 rounded-md border border-blue-200 bg-blue-50/50 p-3 dark:border-blue-900 dark:bg-blue-950/20">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                                {{ __('status_page.incident_metadata.heading') }}
                                            </p>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('status_page.incident_metadata.description') }}
                                            </p>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="incident-type-{{ $incident->id }}"
                                                    :value="__('status_page.incident_metadata.type')" />
                                                <x-select-input id="incident-type-{{ $incident->id }}" name="incident_type" class="mt-1 w-full">
                                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                                    @foreach (\App\Enums\IncidentType::cases() as $incidentType)
                                                        <option value="{{ $incidentType->value }}" @selected(old('incident_type', $incident->incident_type?->value) === $incidentType->value)>
                                                            {{ __('incidents.types.' . $incidentType->value) }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                            </div>
                                            <div>
                                                <x-input-label for="incident-severity-{{ $incident->id }}"
                                                    :value="__('status_page.incident_metadata.severity')" />
                                                <x-select-input id="incident-severity-{{ $incident->id }}" name="severity" class="mt-1 w-full">
                                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                                    @foreach (\App\Enums\IncidentSeverity::cases() as $severity)
                                                        <option value="{{ $severity->value }}" @selected(old('severity', $incident->severity?->value) === $severity->value)>
                                                            {{ __('incidents.severities.' . $severity->value) }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                            </div>
                                            <div>
                                                <x-input-label for="incident-service-{{ $incident->id }}"
                                                    :value="__('status_page.incident_metadata.affected_service')" />
                                                <x-text-input id="incident-service-{{ $incident->id }}" name="affected_service" class="mt-1 w-full"
                                                    :value="old('affected_service', $incident->affected_service)" />
                                            </div>
                                            <div>
                                                <x-input-label for="incident-impact-{{ $incident->id }}"
                                                    :value="__('status_page.incident_metadata.customer_impact')" />
                                                <x-select-input id="incident-impact-{{ $incident->id }}" name="customer_impact" class="mt-1 w-full">
                                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                                    @foreach (\App\Enums\IncidentCustomerImpact::cases() as $impact)
                                                        <option value="{{ $impact->value }}" @selected(old('customer_impact', $incident->customer_impact?->value) === $impact->value)>
                                                            {{ __('incidents.customer_impacts.' . $impact->value) }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                            </div>
                                            <div class="md:col-span-2">
                                                <x-input-label for="incident-category-{{ $incident->id }}"
                                                    :value="__('status_page.incident_metadata.contributing_category')" />
                                                <x-select-input id="incident-category-{{ $incident->id }}" name="contributing_category" class="mt-1 w-full">
                                                    <option value="">{{ __('incidents.analytics.filters.all') }}</option>
                                                    @foreach (\App\Enums\IncidentContributingCategory::cases() as $category)
                                                        <option value="{{ $category->value }}" @selected(old('contributing_category', $incident->contributing_category?->value) === $category->value)>
                                                            {{ __('incidents.contributing_categories.' . $category->value) }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                            </div>
                                        </div>
                                        <x-primary-button>{{ __('status_page.incident_metadata.save') }}</x-primary-button>
                                    </form>

                                    <div class="space-y-3 rounded-md border border-amber-200 bg-amber-50/50 p-3 dark:border-amber-900 dark:bg-amber-950/20">
                                        <div>
                                            <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                                                {{ __('status_page.incident_follow_ups.heading') }}
                                            </p>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('status_page.incident_follow_ups.description') }}
                                            </p>
                                        </div>
                                        @foreach ($incident->followUps as $followUp)
                                            <form method="POST" action="{{ route('status-pages.incident-follow-ups.update', [$statusPage, $incident, $followUp]) }}"
                                                class="space-y-2 rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                                @csrf
                                                @method('PATCH')
                                                <div class="grid gap-2 md:grid-cols-2">
                                                    <x-text-input name="title" :value="$followUp->title" class="w-full" />
                                                    <x-select-input name="status" class="w-full">
                                                        @foreach (\App\Enums\IncidentFollowUpStatus::cases() as $followUpStatus)
                                                            <option value="{{ $followUpStatus->value }}" @selected($followUp->status === $followUpStatus)>
                                                                {{ __('status_page.incident_follow_ups.statuses.' . $followUpStatus->value) }}
                                                            </option>
                                                        @endforeach
                                                    </x-select-input>
                                                    <x-text-input type="date" name="due_at" :value="$followUp->due_at?->format('Y-m-d')" class="w-full" />
                                                    <x-text-input name="external_url" :value="$followUp->external_url" class="w-full" />
                                                </div>
                                                <x-textarea name="description" rows="2">{{ $followUp->description }}</x-textarea>
                                                <div class="flex flex-wrap gap-2">
                                                    <x-primary-button>{{ __('status_page.incident_follow_ups.save') }}</x-primary-button>
                                                            <button type="submit" form="delete-follow-up-{{ $followUp->id }}"
                                                                class="inline-flex h-10 w-10 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 focus:outline-hidden focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-950/30"
                                                                title="{{ __('status_page.incident_follow_ups.delete') }}"
                                                                aria-label="{{ __('status_page.incident_follow_ups.delete') }}">
                                                                <x-icon name="trash" class="h-4 w-4" />
                                                            </button>
                                                </div>
                                            </form>
                                            <form id="delete-follow-up-{{ $followUp->id }}" method="POST"
                                                action="{{ route('status-pages.incident-follow-ups.destroy', [$statusPage, $incident, $followUp]) }}">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endforeach
                                        <form method="POST" action="{{ route('status-pages.incident-follow-ups.store', [$statusPage, $incident]) }}" class="space-y-2">
                                            @csrf
                                            <div class="grid gap-2 md:grid-cols-2">
                                                <x-text-input name="title" :placeholder="__('status_page.incident_follow_ups.title')" class="w-full" />
                                                <x-text-input type="date" name="due_at" :placeholder="__('status_page.incident_follow_ups.due_at')" class="w-full" />
                                                <x-text-input name="external_url" :placeholder="__('status_page.incident_follow_ups.external_url')" class="w-full" />
                                                <x-select-input name="assigned_user_id" class="w-full">
                                                    <option value="">{{ __('status_page.incident_follow_ups.unassigned') }}</option>
                                                    <option value="{{ $statusPage->user_id }}">{{ $statusPage->user->name }}</option>
                                                </x-select-input>
                                            </div>
                                            <x-textarea name="description" rows="2" :placeholder="__('status_page.incident_follow_ups.description_field')"></x-textarea>
                                            <x-primary-button>{{ __('status_page.incident_follow_ups.add') }}</x-primary-button>
                                        </form>
                                    </div>

                                    <div class="space-y-3 rounded-md border border-green-200 bg-green-50/50 p-3 dark:border-green-900 dark:bg-green-950/20">
                                        <div>
                                            <p class="text-sm font-medium text-green-900 dark:text-green-100">
                                                {{ __('status_page.incident_timeline.heading') }}
                                            </p>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('status_page.incident_timeline.description') }}
                                            </p>
                                        </div>
                                        @foreach ($incidentTimelines[$incident->id] ?? [] as $timelineEvent)
                                            <div class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $timelineEvent['title'] }}</p>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $timelineEvent['occurred_at']->toDayDateTimeString() }}</span>
                                                </div>
                                                <p class="mt-1 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                    {{ $timelineEvent['source_type'] === 'custom' ? __('status_page.incident_timeline.custom') : __('status_page.incident_timeline.automatic') }}
                                                </p>
                                                @if ($timelineEvent['description'])
                                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $timelineEvent['description'] }}</p>
                                                @endif
                                                @if ($timelineEvent['id'])
                                                    <form method="POST" action="{{ route('status-pages.incident-timeline.update', [$statusPage, $incident, $timelineEvent['id']]) }}" class="mt-3 space-y-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="grid gap-2 md:grid-cols-2">
                                                            <x-text-input name="title" :value="$timelineEvent['title']" class="w-full" />
                                                            <x-text-input type="datetime-local" name="occurred_at" :value="$timelineEvent['occurred_at']->format('Y-m-d\\TH:i')" class="w-full" />
                                                        </div>
                                                        <x-textarea name="description" rows="2">{{ $timelineEvent['description'] }}</x-textarea>
                                                        <div class="flex flex-wrap gap-2">
                                                            <x-primary-button>{{ __('status_page.incident_timeline.save') }}</x-primary-button>
                                                            <button type="submit" form="delete-timeline-{{ $timelineEvent['id'] }}"
                                                                class="inline-flex h-10 w-10 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 focus:outline-hidden focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-950/30"
                                                                title="{{ __('status_page.incident_timeline.delete') }}"
                                                                aria-label="{{ __('status_page.incident_timeline.delete') }}">
                                                                <x-icon name="trash" class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </form>
                                                    <form id="delete-timeline-{{ $timelineEvent['id'] }}" method="POST"
                                                        action="{{ route('status-pages.incident-timeline.destroy', [$statusPage, $incident, $timelineEvent['id']]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                        <form method="POST" action="{{ route('status-pages.incident-timeline.store', [$statusPage, $incident]) }}" class="space-y-2">
                                            @csrf
                                            <div class="grid gap-2 md:grid-cols-2">
                                                <x-text-input name="title" :placeholder="__('status_page.incident_timeline.title')" class="w-full" />
                                                <x-text-input type="datetime-local" name="occurred_at" class="w-full" />
                                            </div>
                                            <x-textarea name="description" rows="2" :placeholder="__('status_page.incident_timeline.description_field')"></x-textarea>
                                            <x-primary-button>{{ __('status_page.incident_timeline.add') }}</x-primary-button>
                                        </form>
                                    </div>
                                @endif

                                    </div>
                                </details>

                                @if (!Auth::user()->isDemo())
                                    <form method="POST"
                                        action="{{ route('status-pages.incident-review.update', [$statusPage, $incident]) }}"
                                        class="space-y-3 rounded-md border border-purple-200 bg-purple-50/50 p-3 dark:border-purple-900 dark:bg-purple-950/20">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <x-input-label for="incident-problem-{{ $incident->id }}"
                                                :value="__('status_page.incident_review.problem')" />
                                            <x-textarea id="incident-problem-{{ $incident->id }}" name="problem_description"
                                                rows="3" :placeholder="__('status_page.incident_review.problem_placeholder')">{{ old('problem_description') ?: $incident->problem_description }}</x-textarea>
                                            <x-input-error :messages="$errors->get('problem_description')" />
                                        </div>
                                        <div>
                                            <x-input-label for="incident-resolution-{{ $incident->id }}"
                                                :value="__('status_page.incident_review.resolution')" />
                                            <x-textarea id="incident-resolution-{{ $incident->id }}" name="resolution_description"
                                                rows="3" :placeholder="__('status_page.incident_review.resolution_placeholder')">{{ old('resolution_description') ?: $incident->resolution_description }}</x-textarea>
                                            <x-input-error :messages="$errors->get('resolution_description')" />
                                        </div>
                                        <x-primary-button>{{ __('status_page.incident_review.save') }}</x-primary-button>
                                    </form>
                                @elseif ($incident->problem_description || $incident->resolution_description)
                                    <div class="rounded-md border border-purple-200 bg-purple-50/50 p-3 dark:border-purple-900 dark:bg-purple-950/20">
                                        <p class="text-sm font-medium text-purple-900 dark:text-purple-100">
                                            {{ __('status_page.incident_review.heading') }}
                                        </p>
                                        @if ($incident->problem_description)
                                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $incident->problem_description }}</p>
                                        @endif
                                        @if ($incident->resolution_description)
                                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $incident->resolution_description }}</p>
                                        @endif
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

            <aside data-status-page-context-rail class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                <x-container>
                    <x-heading type="h2">{{ __('status_page.detail.summary') }}</x-heading>
                    <dl class="mt-4 divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <div class="flex items-center justify-between gap-3 py-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('status_page.detail.owner') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $statusPage->user->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('status_page.detail.components') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $statusPage->components->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('status_page.detail.incidents') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $incidents->count() }}</dd>
                        </div>
                    </dl>
                    @if ($statusPage->is_public)
                        <a href="{{ route('public-status-pages.show', $statusPage) }}" target="_blank" rel="noopener"
                            class="mt-4 inline-flex h-10 w-10 items-center justify-center rounded-md border border-purple-300 text-purple-700 hover:bg-purple-50 focus:outline-hidden focus:ring-2 focus:ring-purple-500 dark:border-purple-700 dark:text-purple-300 dark:hover:bg-purple-950/30"
                            title="{{ __('status_page.detail.open_public_page') }}" aria-label="{{ __('status_page.detail.open_public_page') }}">
                            <x-icon name="external-link" class="h-4 w-4" />
                        </a>
                    @endif
                </x-container>

                <x-container>
                    <x-heading type="h2">{{ __('status_page.detail.incident_context') }}</x-heading>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('status_page.incident_workbench.description') }}</p>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-lg bg-purple-50 p-3 text-purple-900 dark:bg-purple-950/30 dark:text-purple-100">
                            <p class="font-semibold">{{ __('status_page.detail.root_cause') }}</p>
                            <p class="mt-1 text-purple-800/80 dark:text-purple-200/80">{{ __('status_page.incident_review.problem') }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                            <p class="font-semibold">{{ __('status_page.detail.request_response') }}</p>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">{{ __('status_page.detail.regions_activity') }}</p>
                        </div>
                    </div>
                </x-container>
            </aside>
        </div>
    </x-main>
</x-app-layout>
