<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class MaintenanceDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $manageableMonitoringIds = Monitoring::query()
            ->manageableBy($user)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('maintenance_status')->toString();
        $groupId = $request->string('monitoring_group_id')->toString();
        $sort = $request->string('sort')->toString() ?: 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $windowsQuery = Monitoring::query()
            ->visibleTo($user)
            ->select(['id', 'name', 'target', 'maintenance_from', 'maintenance_until'])
            ->with('groups:id,name');

        if ($search !== '') {
            $windowsQuery->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('target', 'like', '%' . $search . '%')
                    ->orWhereHas('groups', fn (Builder $builder): Builder => $builder->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($groupId !== '') {
            $windowsQuery->whereHas('groups', fn (Builder $builder): Builder => $builder->whereKey($groupId));
        }

        $this->applyMaintenanceStatusFilter($windowsQuery, $status);

        if ($sort === 'maintenance_status') {
            $now = Date::now();
            $windowsQuery
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
        } else {
            $windowsQuery->orderBy(in_array($sort, ['name', 'maintenance_from', 'maintenance_until'], true) ? $sort : 'name', $direction)
                ->orderBy('name')
                ->orderBy('id');
        }

        $windows = $windowsQuery->paginate(min(max($request->integer('per_page', 50), 1), 100));

        $windowData = $windows->through(static fn (Monitoring $monitoring): array => [
            'id' => (string) $monitoring->id,
            'name' => $monitoring->name,
            'target' => $monitoring->target,
            'groups' => $monitoring->groups->map(static fn ($group): array => [
                'id' => (string) $group->id,
                'name' => $group->name,
            ])->values()->all(),
            'status' => match (true) {
                $monitoring->isUnderMaintenance() => 'active',
                $monitoring->maintenance_from?->isFuture() === true => 'upcoming',
                $monitoring->maintenance_from !== null => 'expired',
                default => 'none',
            },
            'maintenance_from' => $monitoring->maintenance_from?->toDayDateTimeString(),
            'maintenance_until' => $monitoring->maintenance_until?->toDayDateTimeString(),
            'can_manage' => in_array((string) $monitoring->id, $manageableMonitoringIds, true),
        ]);

        $visibleMonitorings = Monitoring::query()
            ->visibleTo($user)
            ->get(['id', 'maintenance_from', 'maintenance_until']);

        $activeCount = $visibleMonitorings->filter(static fn (Monitoring $monitoring): bool => $monitoring->isUnderMaintenance())->count();
        $upcomingCount = $visibleMonitorings->filter(static fn (Monitoring $monitoring): bool => ! $monitoring->isUnderMaintenance()
            && $monitoring->hasUpcomingMaintenance())->count();
        $expiredCount = $visibleMonitorings->filter(static fn (Monitoring $monitoring): bool => ! $monitoring->isUnderMaintenance()
            && $monitoring->maintenance_from !== null
            && ! $monitoring->maintenance_from->isFuture())->count();

        return response()->json([
            'data' => [
                'can_manage_maintenance' => $manageableMonitoringIds !== [],
                'windows' => $windowData,
                'stats' => [
                    'total' => $visibleMonitorings->count(),
                    'active' => $activeCount,
                    'upcoming' => $upcomingCount,
                    'expired' => $expiredCount,
                    'none' => $visibleMonitorings->count() - $activeCount - $upcomingCount - $expiredCount,
                ],
                'recurring_windows' => MaintenanceWindow::query()
                    ->visibleTo($user)
                    ->with([
                        'monitoring:id,name',
                        'monitoringGroup:id,name',
                    ])
                    ->latest('starts_at')
                    ->get()
                    ->map(static fn (MaintenanceWindow $window): array => [
                        'id' => (string) $window->id,
                        'target' => $window->monitoring?->name ?? $window->monitoringGroup?->name,
                        'recurrence' => $window->recurrence->value,
                        'duration_minutes' => $window->duration_minutes,
                        'timezone' => $window->timezone,
                        'starts_at' => $window->starts_at->setTimezone($window->timezone)->toDayDateTimeString(),
                        'can_manage' => $window->isManageableBy($user),
                    ])
                    ->values()
                    ->all(),
                'monitoring_options' => Monitoring::query()
                    ->manageableBy($user)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(static fn (Monitoring $monitoring): array => [
                        'id' => (string) $monitoring->id,
                        'name' => $monitoring->name,
                    ])
                    ->values()
                    ->all(),
                'monitoring_groups' => $user->monitoringGroups()
                    ->withCount('monitorings')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(static fn ($group): array => [
                        'id' => (string) $group->id,
                        'name' => $group->name,
                        'monitorings_count' => (int) $group->monitorings_count,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    private function applyMaintenanceStatusFilter(Builder $builder, string $status): void
    {
        if ($status === '') {
            return;
        }

        $now = Date::now();

        match ($status) {
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
}
