<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Monitoring;
use App\Models\User;

final class MonitoringDetailQuery
{
    public function findVisible(User $user, string $monitoringId): Monitoring
    {
        return Monitoring::query()
            ->visibleTo($user)
            ->withMaintenanceWindowState()
            ->with([
                'domainResult',
                'groups',
                'latestIncident',
                'latestResponseResult',
                'performanceState',
                'sslResult',
                'statusPageComponents.statusPage',
                'team.users',
                'user',
            ])
            ->findOrFail($monitoringId);
    }
}
