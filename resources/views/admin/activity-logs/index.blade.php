@php
    use App\Models\Monitoring;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Model;

    $subjectLabel = static function (?string $subjectType, mixed $subjectId) use ($subjectTypes): string {
        if (! $subjectType || ! $subjectId) {
            return __('admin.activity_logs.messages.unknown_subject');
        }

        $type = $subjectTypes[$subjectType] ?? class_basename($subjectType);

        return $type . ' #' . $subjectId;
    };

    $actorLabel = static function (?Model $actor): string {
        if (! $actor instanceof User) {
            return __('admin.activity_logs.messages.anonymous');
        }

        return $actor->email;
    };

    $json = static function (mixed $value): string {
        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : '{}';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-heading>{{ __('admin.activity_logs.title') }}</x-heading>

        <x-secondary-button :href="route('admin.dashboard')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <form method="GET" action="{{ route('admin.activity-logs.index') }}"
            class="mb-4 grid gap-3 rounded-md bg-white p-4 shadow-md dark:bg-gray-800 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <x-input-label for="log_name" :value="__('admin.activity_logs.filters.log_name')" />
                <select id="log_name" name="log_name"
                    class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">{{ __('search.filter.all') }}</option>
                    @foreach ($logNames as $logName)
                        <option value="{{ $logName }}" @selected(request('log_name') === $logName)>{{ $logName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="event" :value="__('admin.activity_logs.filters.event')" />
                <select id="event" name="event"
                    class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">{{ __('search.filter.all') }}</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="causer_id" :value="__('admin.activity_logs.filters.actor')" />
                <select id="causer_id" name="causer_id"
                    class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">{{ __('search.filter.all') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(request('causer_id') === $user->id)>{{ $user->email }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="subject_type" :value="__('admin.activity_logs.filters.subject_type')" />
                <select id="subject_type" name="subject_type"
                    class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">{{ __('search.filter.all') }}</option>
                    @foreach ($subjectTypes as $subjectType => $label)
                        <option value="{{ $subjectType }}" @selected(request('subject_type') === $subjectType)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="subject_id" :value="__('admin.activity_logs.filters.subject_id')" />
                <x-text-input id="subject_id" name="subject_id" :value="request('subject_id')" class="mt-1 w-full" />
            </div>

            <div>
                <x-input-label for="date_from" :value="__('admin.activity_logs.filters.date_from')" />
                <x-text-input id="date_from" type="date" name="date_from" :value="request('date_from')" class="mt-1 w-full" />
            </div>

            <div>
                <x-input-label for="date_to" :value="__('admin.activity_logs.filters.date_to')" />
                <x-text-input id="date_to" type="date" name="date_to" :value="request('date_to')" class="mt-1 w-full" />
            </div>

            <div class="flex items-end gap-2">
                <x-primary-button type="submit">
                    {{ __('admin.activity_logs.filters.apply') }}
                </x-primary-button>
                <x-secondary-button :href="route('admin.activity-logs.index')">
                    {{ __('admin.activity_logs.filters.reset') }}
                </x-secondary-button>
            </div>
        </form>

        <x-table>
            <x-slot name="head">
                <x-table.heading>{{ __('admin.activity_logs.fields.created_at') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.actor') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.log_name') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.event') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.subject') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.description') }}</x-table.heading>
                <x-table.heading>{{ __('admin.activity_logs.fields.changes') }}</x-table.heading>
            </x-slot>

            <x-slot name="body">
                @forelse ($activities as $activity)
                    <x-table.row>
                        <td class="whitespace-nowrap px-6 py-4 text-gray-900 dark:text-gray-100">
                            {{ $activity->created_at?->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-gray-900 dark:text-gray-100">
                            {{ $actorLabel($activity->causer) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-gray-900 dark:text-gray-100">
                            {{ $activity->log_name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-gray-900 dark:text-gray-100">
                            {{ $activity->event }}
                        </td>
                        <td class="max-w-xs whitespace-normal px-6 py-4 text-gray-900 dark:text-gray-100">
                            {{ $subjectLabel($activity->subject_type, $activity->subject_id) }}
                        </td>
                        <td class="max-w-xs whitespace-normal px-6 py-4 text-gray-900 dark:text-gray-100">
                            {{ $activity->description }}
                        </td>
                        <td class="min-w-96 whitespace-normal px-6 py-4 text-gray-900 dark:text-gray-100">
                            <details>
                                <summary class="cursor-pointer text-purple-600 hover:underline dark:text-purple-300">
                                    {{ __('admin.activity_logs.messages.show_changes') }}
                                </summary>
                                <pre class="mt-3 max-h-96 overflow-auto rounded bg-gray-100 p-3 text-xs leading-5 text-gray-900 dark:bg-gray-900 dark:text-gray-100">{{ $json($activity->attribute_changes?->toArray() ?? []) }}</pre>
                                @if (($activity->properties?->count() ?? 0) > 0)
                                    <pre class="mt-3 max-h-96 overflow-auto rounded bg-gray-100 p-3 text-xs leading-5 text-gray-900 dark:bg-gray-900 dark:text-gray-100">{{ $json(['properties' => $activity->properties?->toArray() ?? []]) }}</pre>
                                @endif
                            </details>
                        </td>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colSpan="7" class="text-center text-gray-500">
                            {{ __('admin.activity_logs.messages.empty') }}
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $activities->withQueryString()->links() }}
        </div>
    </x-main>
</x-app-layout>
