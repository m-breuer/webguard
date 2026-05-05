<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\User;
use App\Support\Admin\AsyncTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate(AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
            'log_name' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
            'causer_id' => ['nullable', 'string', 'exists:users,id'],
            'subject_type' => ['nullable', 'string', 'in:' . implode(',', [User::class, Monitoring::class])],
            'subject_id' => ['nullable', 'string', 'max:36'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ], ['created_at', 'log_name', 'event', 'description']));
        $table = AsyncTable::options($validated, 'created_at', 'desc', 25);
        $subjectTypes = [
            User::class => __('admin.activity_logs.subject_types.user'),
            Monitoring::class => __('admin.activity_logs.subject_types.monitoring'),
        ];

        $lengthAwarePaginator = Activity::query()
            ->with('causer')
            ->when($validated['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $builder) use ($search): void {
                    $builder->where('description', 'like', '%' . $search . '%')
                        ->orWhere('log_name', 'like', '%' . $search . '%')
                        ->orWhere('event', 'like', '%' . $search . '%')
                        ->orWhere('subject_id', 'like', '%' . $search . '%');
                });
            })
            ->when($validated['log_name'] ?? null, fn (Builder $builder, string $logName): Builder => $builder->where('log_name', $logName))
            ->when($validated['event'] ?? null, fn (Builder $builder, string $event): Builder => $builder->where('event', $event))
            ->when($validated['causer_id'] ?? null, fn (Builder $builder, string $causerId): Builder => $builder->where('causer_type', User::class)->where('causer_id', $causerId))
            ->when($validated['subject_type'] ?? null, fn (Builder $builder, string $subjectType): Builder => $builder->where('subject_type', $subjectType))
            ->when($validated['subject_id'] ?? null, fn (Builder $builder, string $subjectId): Builder => $builder->where('subject_id', $subjectId))
            ->when($validated['date_from'] ?? null, fn (Builder $builder, string $dateFrom): Builder => $builder->whereDate('created_at', '>=', $dateFrom))
            ->when($validated['date_to'] ?? null, fn (Builder $builder, string $dateTo): Builder => $builder->whereDate('created_at', '<=', $dateTo))
            ->orderBy($table->sort, $table->direction)
            ->orderBy('id')
            ->paginate($table->perPage);

        if ($request->expectsJson()) {
            return AsyncTable::json(
                $lengthAwarePaginator,
                'admin.activity-logs.partials.rows',
                [
                    'activities' => $lengthAwarePaginator,
                    'subjectTypes' => $subjectTypes,
                ]
            );
        }

        $users = User::query()->select('id', 'email')->orderBy('email')->get();
        $logNames = Activity::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name');
        $events = Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event');

        return view('admin.activity-logs.index', [
            'activities' => $lengthAwarePaginator,
            'subjectTypes' => $subjectTypes,
            'filters' => [
                [
                    'name' => 'log_name',
                    'label' => __('admin.activity_logs.filters.log_name'),
                    'placeholder' => __('admin.activity_logs.filters.log_name'),
                    'options' => $logNames->mapWithKeys(fn (string $logName): array => [$logName => $logName])->all(),
                ],
                [
                    'name' => 'event',
                    'label' => __('admin.activity_logs.filters.event'),
                    'placeholder' => __('admin.activity_logs.filters.event'),
                    'options' => $events->mapWithKeys(fn (string $event): array => [$event => $event])->all(),
                ],
                [
                    'name' => 'causer_id',
                    'label' => __('admin.activity_logs.filters.actor'),
                    'placeholder' => __('admin.activity_logs.filters.actor'),
                    'options' => $users->mapWithKeys(fn (User $user): array => [$user->id => $user->email])->all(),
                ],
                [
                    'name' => 'subject_type',
                    'label' => __('admin.activity_logs.filters.subject_type'),
                    'placeholder' => __('admin.activity_logs.filters.subject_type'),
                    'options' => $subjectTypes,
                ],
                [
                    'name' => 'subject_id',
                    'label' => __('admin.activity_logs.filters.subject_id'),
                    'placeholder' => __('admin.activity_logs.filters.subject_id'),
                    'type' => 'text',
                ],
                [
                    'name' => 'date_from',
                    'label' => __('admin.activity_logs.filters.date_from'),
                    'placeholder' => __('admin.activity_logs.filters.date_from'),
                    'type' => 'date',
                ],
                [
                    'name' => 'date_to',
                    'label' => __('admin.activity_logs.filters.date_to'),
                    'placeholder' => __('admin.activity_logs.filters.date_to'),
                    'type' => 'date',
                ],
            ],
            'activeFilters' => [
                'log_name' => (string) ($validated['log_name'] ?? ''),
                'event' => (string) ($validated['event'] ?? ''),
                'causer_id' => (string) ($validated['causer_id'] ?? ''),
                'subject_type' => (string) ($validated['subject_type'] ?? ''),
                'subject_id' => (string) ($validated['subject_id'] ?? ''),
                'date_from' => (string) ($validated['date_from'] ?? ''),
                'date_to' => (string) ($validated['date_to'] ?? ''),
            ],
            'sort' => $table->sort,
            'direction' => $table->direction,
        ]);
    }
}
