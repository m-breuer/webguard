<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\User;
use App\Support\Admin\AsyncTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validate(AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
            'maintenance_status' => ['nullable', 'string', 'in:active,upcoming,expired,none'],
            'monitoring_group_id' => ['nullable', 'string', 'exists:monitoring_groups,id'],
        ], ['name', 'maintenance_status', 'maintenance_from', 'maintenance_until']));
        $asyncTableOptions = AsyncTable::options($validated, 'name', 'asc', 25);
        $manageableMonitorings = ! $user->isDemo()
            ? Monitoring::query()
                ->manageableBy($user)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();
        $canManageMaintenance = $manageableMonitorings->isNotEmpty();
        $monitorings = Monitoring::query()
            ->visibleTo($user)
            ->with('groups:id,name')
            ->orderBy('name')
            ->get();
        $recurringWindows = MaintenanceWindow::query()
            ->visibleTo($user)
            ->with([
                'monitoring:id,name,user_id,team_id',
                'monitoringGroup:id,name,user_id',
            ])
            ->latest('starts_at')
            ->get();
        $activeMaintenanceCount = $monitorings
            ->filter(static fn (Monitoring $monitoring): bool => $monitoring->isUnderMaintenance())
            ->count();
        $upcomingMaintenanceCount = $monitorings
            ->filter(static fn (Monitoring $monitoring): bool => ! $monitoring->isUnderMaintenance()
                && $monitoring->hasUpcomingMaintenance())
            ->count();
        $expiredMaintenanceCount = $monitorings
            ->filter(static fn (Monitoring $monitoring): bool => ! $monitoring->isUnderMaintenance()
                && $monitoring->maintenance_from !== null
                && ! $monitoring->maintenance_from->isFuture())
            ->count();
        $tableQuery = Monitoring::query()
            ->visibleTo($user)
            ->with('groups:id,name')
            ->when($validated['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $builder) use ($search): void {
                    $builder->where('name', 'like', '%' . $search . '%')
                        ->orWhere('target', 'like', '%' . $search . '%')
                        ->orWhereHas('groups', fn (Builder $builder): Builder => $builder->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($validated['monitoring_group_id'] ?? null, fn (Builder $builder, string $groupId): Builder => $builder->whereHas('groups', fn (Builder $builder): Builder => $builder->where('monitoring_groups.id', $groupId)));

        $this->applyMaintenanceStatusFilter($tableQuery, (string) ($validated['maintenance_status'] ?? ''));
        $this->applyMaintenanceSort($tableQuery, $asyncTableOptions->sort, $asyncTableOptions->direction);

        $lengthAwarePaginator = $tableQuery->paginate($asyncTableOptions->perPage);

        if ($request->expectsJson()) {
            return AsyncTable::json($lengthAwarePaginator, 'maintenance.partials.rows', [
                'monitorings' => $lengthAwarePaginator,
                'canManageMaintenance' => $canManageMaintenance,
                'manageableMonitoringIds' => $manageableMonitorings->pluck('id')->all(),
            ]);
        }

        return view('maintenance.index', [
            'monitorings' => $lengthAwarePaginator,
            'maintenanceStats' => [
                'total' => $monitorings->count(),
                'active' => $activeMaintenanceCount,
                'upcoming' => $upcomingMaintenanceCount,
                'expired' => $expiredMaintenanceCount,
                'none' => $monitorings->count() - $activeMaintenanceCount - $upcomingMaintenanceCount - $expiredMaintenanceCount,
            ],
            'recurringWindows' => $recurringWindows,
            'manageableMonitorings' => $manageableMonitorings,
            'manageableMonitoringIds' => $manageableMonitorings->pluck('id')->all(),
            'monitoringGroups' => $user->monitoringGroups()
                ->withCount('monitorings')
                ->orderBy('name')
                ->get(['id', 'name']),
            'canManageMaintenance' => $canManageMaintenance,
            'filters' => [
                [
                    'name' => 'maintenance_status',
                    'label' => __('maintenance.table.status_filter'),
                    'placeholder' => __('search.filter.text', ['attribute' => __('maintenance.table.status_filter')]),
                    'options' => [
                        'active' => __('maintenance.status.active'),
                        'upcoming' => __('maintenance.status.upcoming'),
                        'expired' => __('maintenance.status.expired'),
                        'none' => __('maintenance.status.none'),
                    ],
                ],
                [
                    'name' => 'monitoring_group_id',
                    'label' => __('maintenance.table.group_filter'),
                    'placeholder' => __('search.filter.text', ['attribute' => __('maintenance.table.group_filter')]),
                    'options' => $user->monitoringGroups()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all(),
                ],
            ],
            'activeFilters' => [
                'maintenance_status' => (string) ($validated['maintenance_status'] ?? ''),
                'monitoring_group_id' => (string) ($validated['monitoring_group_id'] ?? ''),
            ],
            'sort' => $asyncTableOptions->sort,
            'direction' => $asyncTableOptions->direction,
        ]);
    }

    public function store(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_if($maintenanceRequest->user()?->isDemo(), 403);

        $validated = $maintenanceRequest->validated();

        if ($validated['mode'] === 'recurring') {
            $timezone = $validated['recurring_timezone'];
            $startsAt = Date::parse($validated['recurring_starts_at'], $timezone)->setTimezone('UTC');

            MaintenanceWindow::query()->create([
                ...$this->recurringTarget($maintenanceRequest),
                'starts_at' => $startsAt,
                'duration_minutes' => (int) $validated['recurring_duration_minutes'],
                'recurrence' => $validated['recurrence'],
                'repeat_until' => isset($validated['recurring_repeat_until'])
                    ? Date::parse($validated['recurring_repeat_until'], $timezone)->endOfDay()->setTimezone('UTC')
                    : null,
                'timezone' => $timezone,
                'enabled' => true,
            ]);

            return to_route('maintenance.index')->with('success', __('maintenance.messages.recurring_scheduled'));
        }

        $updatedCount = $this->targetMonitorings($maintenanceRequest)
            ->update([
                'maintenance_from' => Date::parse($validated['maintenance_from']),
                'maintenance_until' => isset($validated['maintenance_until'])
                    ? Date::parse($validated['maintenance_until'])
                    : null,
            ]);

        return to_route('maintenance.index')
            ->with('success', trans_choice('maintenance.messages.scheduled', $updatedCount, ['count' => $updatedCount]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_if($request->user()?->isDemo(), 403);
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'monitoring_id' => [
                'nullable',
                'required_without:maintenance_window_id',
                'string',
                function ($attribute, $value, $fail) use ($user): void {
                    if (! Monitoring::query()->manageableBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => __('maintenance.form.monitoring')]));
                    }
                },
            ],
            'maintenance_window_id' => [
                'nullable',
                'required_without:monitoring_id',
                'string',
                function ($attribute, $value, $fail) use ($user): void {
                    $window = MaintenanceWindow::query()->find((string) $value);

                    if (! $window || ! $window->isManageableBy($user)) {
                        $fail(__('validation.exists', ['attribute' => __('maintenance.form.recurring')]));
                    }
                },
            ],
        ]);

        if (isset($validated['maintenance_window_id'])) {
            MaintenanceWindow::query()->whereKey($validated['maintenance_window_id'])->update(['enabled' => false]);

            return to_route('maintenance.index')->with('success', __('maintenance.messages.recurring_cleared'));
        }

        Monitoring::query()
            ->manageableBy($user)
            ->whereKey($validated['monitoring_id'])
            ->update([
                'maintenance_from' => null,
                'maintenance_until' => null,
            ]);

        return to_route('maintenance.index')
            ->with('success', __('maintenance.messages.cleared'));
    }

    private function targetMonitorings(MaintenanceRequest $maintenanceRequest): Builder
    {
        /** @var User $user */
        $user = $maintenanceRequest->user();
        $query = Monitoring::query()->manageableBy($user);

        if ($maintenanceRequest->string('scope')->toString() === 'group') {
            return $query->whereHas('groups', function ($builder) use ($maintenanceRequest): void {
                $builder->where('monitoring_groups.id', $maintenanceRequest->string('monitoring_group_id')->toString());
            });
        }

        return $query->whereKey($maintenanceRequest->string('monitoring_id')->toString());
    }

    /**
     * @return array{monitoring_id?: string, monitoring_group_id?: string}
     */
    private function recurringTarget(MaintenanceRequest $maintenanceRequest): array
    {
        if ($maintenanceRequest->string('scope')->toString() === 'group') {
            return ['monitoring_group_id' => $maintenanceRequest->string('monitoring_group_id')->toString()];
        }

        return ['monitoring_id' => $maintenanceRequest->string('monitoring_id')->toString()];
    }

    private function applyMaintenanceStatusFilter(Builder $builder, string $maintenanceStatus): void
    {
        if ($maintenanceStatus === '') {
            return;
        }

        $now = Date::now();

        match ($maintenanceStatus) {
            'active' => $builder
                ->whereNotNull('maintenance_from')
                ->where('maintenance_from', '<=', $now)
                ->where(function (Builder $builder) use ($now): void {
                    $builder->whereNull('maintenance_until')
                        ->orWhere('maintenance_until', '>=', $now);
                }),
            'upcoming' => $builder
                ->whereNotNull('maintenance_from')
                ->where('maintenance_from', '>', $now),
            'expired' => $builder
                ->whereNotNull('maintenance_from')
                ->whereNotNull('maintenance_until')
                ->where('maintenance_until', '<', $now),
            'none' => $builder->whereNull('maintenance_from'),
            default => null,
        };
    }

    private function applyMaintenanceSort(Builder $builder, string $sort, string $direction): void
    {
        if ($sort === 'maintenance_status') {
            $now = Date::now();
            $builder
                ->orderByRaw(
                    "case
                        when maintenance_from is not null and maintenance_from <= ? and (maintenance_until is null or maintenance_until >= ?) then 0
                        when maintenance_from is not null and maintenance_from > ? then 1
                        when maintenance_from is not null then 2
                        else 3
                    end {$direction}",
                    [$now, $now, $now]
                )
                ->orderBy('name');

            return;
        }

        $builder
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->orderBy('id');
    }
}
