<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class MonitoringDataQuery
{
    public function findAccessible(?User $user, string $monitoringId): Monitoring
    {
        return Monitoring::query()
            ->where(function (Builder $builder) use ($user): void {
                if ($user !== null) {
                    $builder->visibleTo($user);
                }

                $builder->orWhere('public_label_enabled', true);
            })
            ->findOrFail($monitoringId);
    }
}
