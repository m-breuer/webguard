<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class MonitoringDataQuery
{
    public function findAccessible(?User $actor, string $monitoringId): Monitoring
    {
        return Monitoring::query()
            ->where(function (Builder $builder) use ($actor): void {
                if ($actor !== null) {
                    $builder->visibleTo($actor);
                }

                $builder->orWhere('public_label_enabled', true);
            })
            ->findOrFail($monitoringId);
    }
}
