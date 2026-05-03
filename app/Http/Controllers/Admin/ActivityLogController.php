<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'log_name' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
            'causer_id' => ['nullable', 'string', 'exists:users,id'],
            'subject_type' => ['nullable', 'string', 'in:' . implode(',', [User::class, Monitoring::class])],
            'subject_id' => ['nullable', 'string', 'max:36'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $lengthAwarePaginator = Activity::query()
            ->with('causer')
            ->when($validated['log_name'] ?? null, fn (Builder $builder, string $logName): Builder => $builder->where('log_name', $logName))
            ->when($validated['event'] ?? null, fn (Builder $builder, string $event): Builder => $builder->where('event', $event))
            ->when($validated['causer_id'] ?? null, fn (Builder $builder, string $causerId): Builder => $builder->where('causer_type', User::class)->where('causer_id', $causerId))
            ->when($validated['subject_type'] ?? null, fn (Builder $builder, string $subjectType): Builder => $builder->where('subject_type', $subjectType))
            ->when($validated['subject_id'] ?? null, fn (Builder $builder, string $subjectId): Builder => $builder->where('subject_id', $subjectId))
            ->when($validated['date_from'] ?? null, fn (Builder $builder, string $dateFrom): Builder => $builder->whereDate('created_at', '>=', $dateFrom))
            ->when($validated['date_to'] ?? null, fn (Builder $builder, string $dateTo): Builder => $builder->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(25);

        return view('admin.activity-logs.index', [
            'activities' => $lengthAwarePaginator,
            'users' => User::query()->select('id', 'email')->orderBy('email')->get(),
            'logNames' => Activity::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
            'events' => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event'),
            'subjectTypes' => [
                User::class => __('admin.activity_logs.subject_types.user'),
                Monitoring::class => __('admin.activity_logs.subject_types.monitoring'),
            ],
        ]);
    }
}
