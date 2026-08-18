<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\User;
use App\Queries\MaintenanceOverviewQuery;

final class MaintenanceOverviewService
{
    public function __construct(private readonly MaintenanceOverviewQuery $maintenanceOverviewQuery) {}

    /**
     * @return array<string, mixed>
     */
    public function for(
        User $user,
        string $search,
        string $status,
        string $groupId,
        string $sort,
        string $direction,
        int $perPage,
    ): array {
        $manageableMonitoringIds = $this->maintenanceOverviewQuery->manageableMonitoringIdsFor($user);
        $lengthAwarePaginator = $this->maintenanceOverviewQuery->paginateWindowsFor(
            $user,
            $search,
            $status,
            $groupId,
            $sort,
            $direction,
            $perPage,
        );
        $windowData = $lengthAwarePaginator->through(static fn (Monitoring $monitoring): array => [
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
            'maintenance_from' => $monitoring->maintenance_from?->toIso8601String(),
            'maintenance_until' => $monitoring->maintenance_until?->toIso8601String(),
            'can_manage' => in_array((string) $monitoring->id, $manageableMonitoringIds, true),
        ]);

        $visibleMonitorings = $this->maintenanceOverviewQuery->visibleMaintenanceStatesFor($user);
        $activeCount = $visibleMonitorings->filter(static fn (Monitoring $monitoring): bool => $monitoring->isUnderMaintenance())->count();
        $upcomingCount = $visibleMonitorings->filter(static fn (Monitoring $monitoring): bool => ! $monitoring->isUnderMaintenance()
            && $monitoring->hasUpcomingMaintenance())->count();
        $expiredCount = $visibleMonitorings->filter(static fn (Monitoring $monitoring): bool => ! $monitoring->isUnderMaintenance()
            && $monitoring->maintenance_from !== null
            && ! $monitoring->maintenance_from->isFuture())->count();

        return [
            'can_manage_maintenance' => $manageableMonitoringIds !== [],
            'windows' => $windowData,
            'stats' => [
                'total' => $visibleMonitorings->count(),
                'active' => $activeCount,
                'upcoming' => $upcomingCount,
                'expired' => $expiredCount,
                'none' => $visibleMonitorings->count() - $activeCount - $upcomingCount - $expiredCount,
            ],
            'recurring_windows' => $this->maintenanceOverviewQuery->recurringWindowsFor($user)
                ->map(static fn (MaintenanceWindow $maintenanceWindow): array => [
                    'id' => (string) $maintenanceWindow->id,
                    'target' => $maintenanceWindow->monitoring?->name ?? $maintenanceWindow->monitoringGroup?->name,
                    'recurrence' => $maintenanceWindow->recurrence->value,
                    'duration_minutes' => $maintenanceWindow->duration_minutes,
                    'timezone' => $maintenanceWindow->timezone,
                    'starts_at' => $maintenanceWindow->starts_at->toIso8601String(),
                    'repeat_until' => $maintenanceWindow->repeat_until?->toIso8601String(),
                    'scope' => $maintenanceWindow->monitoring_id !== null ? 'monitoring' : 'group',
                    'monitoring_id' => $maintenanceWindow->monitoring_id,
                    'monitoring_group_id' => $maintenanceWindow->monitoring_group_id,
                    'enabled' => $maintenanceWindow->enabled,
                    'can_manage' => $maintenanceWindow->isManageableBy($user),
                ])
                ->values()
                ->all(),
            'monitoring_options' => $this->maintenanceOverviewQuery->manageableMonitoringOptionsFor($user)
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
        ];
    }
}
