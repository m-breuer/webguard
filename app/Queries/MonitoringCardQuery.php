<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class MonitoringCardQuery
{
    /**
     * @param  list<string>  $monitoringIds
     * @return EloquentCollection<int, Monitoring>
     */
    public function for(User $user, array $monitoringIds): EloquentCollection
    {
        if ($monitoringIds === []) {
            return new EloquentCollection();
        }

        return Monitoring::query()
            ->select([
                'id',
                'user_id',
                'team_id',
                'status',
                'name',
                'target',
                'type',
                'created_at',
                'maintenance_from',
                'maintenance_until',
                'preferred_location',
                'preferred_locations',
                'heartbeat_interval_minutes',
                'heartbeat_grace_minutes',
                'heartbeat_last_ping_at',
            ])
            ->withMaintenanceWindowState()
            ->visibleTo($user)
            ->whereIn('id', $monitoringIds)
            ->with([
                'latestIncident',
                'latestResponseResult',
            ])
            ->get();
    }
}
