<?php

declare(strict_types=1);

namespace App\Services\Monitorings;

use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\User;

class MonitoringGroupAssignmentService
{
    /**
     * @param  list<string>  $monitoringGroupIds
     */
    public function syncGroupsForPrivateMonitoring(
        Monitoring $monitoring,
        User $user,
        array $monitoringGroupIds
    ): void {
        abort_unless($monitoring->isPrivateOwned() && $monitoring->user_id === $user->id, 403);

        $ownedGroupIds = $user->monitoringGroups()
            ->whereKey($monitoringGroupIds)
            ->pluck('monitoring_groups.id');

        $monitoring->groups()->sync($ownedGroupIds->all());
    }

    /**
     * @param  list<string>  $monitoringIds
     */
    public function syncAssignableMonitorings(
        MonitoringGroup $monitoringGroup,
        User $user,
        array $monitoringIds
    ): void {
        $existingIds = Monitoring::query()->withoutGlobalScopes()
            ->whereHas('groups', fn ($query) => $query->whereKey($monitoringGroup->id))
            ->pluck('monitorings.id');

        $assignableExistingIds = Monitoring::query()->withoutGlobalScopes()
            ->privateOwnedBy($user)
            ->whereKey($existingIds)
            ->pluck('monitorings.id');

        $retainedIds = $existingIds->diff($assignableExistingIds);

        $monitoringGroup->monitorings()->sync(
            $retainedIds->merge($monitoringIds)->unique()->values()->all()
        );
    }
}
