@php
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

@forelse ($activities as $activity)
    <x-table.row>
        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
            @if ($activity->created_at)
                <x-date-time :value="$activity->created_at" format="datetime_seconds" />
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
            {{ $actorLabel($activity->causer) }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $activity->log_name }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $activity->event }}</td>
        <td class="max-w-xs px-6 py-4 whitespace-normal text-gray-900 dark:text-gray-100">
            {{ $subjectLabel($activity->subject_type, $activity->subject_id) }}
        </td>
        <td class="max-w-xs px-6 py-4 whitespace-normal text-gray-900 dark:text-gray-100">
            {{ $activity->description }}
        </td>
        <td class="min-w-96 px-6 py-4 whitespace-normal text-gray-900 dark:text-gray-100">
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
