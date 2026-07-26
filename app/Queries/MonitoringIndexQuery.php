<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\MonitoringIndexFilters;
use App\Data\MonitoringIndexReadModel;
use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

final class MonitoringIndexQuery
{
    public function for(User $user, MonitoringIndexFilters $monitoringIndexFilters, int $perPage = 5): MonitoringIndexReadModel
    {
        $query = $this->query($user, $monitoringIndexFilters);
        $summaryMonitoringIds = (clone $query)->select('id')->reorder()->pluck('id')->values();
        $monitorings = $query->withMaintenanceWindowState()->paginate($perPage);

        return new MonitoringIndexReadModel(
            monitorings: $monitorings,
            summaryMonitoringIds: $summaryMonitoringIds,
            total: $monitorings->total(),
        );
    }

    /**
     * @return Builder<Monitoring>
     */
    private function query(User $user, MonitoringIndexFilters $monitoringIndexFilters): Builder
    {
        $query = Monitoring::query()
            ->visibleTo($user)
            ->select([
                'id',
                'name',
                'target',
                'type',
                'status',
                'public_label_enabled',
                'maintenance_from',
                'maintenance_until',
            ]);

        if ($monitoringIndexFilters->search !== null) {
            $query->where(function (Builder $builder) use ($monitoringIndexFilters): void {
                $builder->where('name', 'like', '%' . $monitoringIndexFilters->search . '%')
                    ->orWhere('target', 'like', '%' . $monitoringIndexFilters->search . '%')
                    ->orWhere('port', 'like', '%' . $monitoringIndexFilters->search . '%')
                    ->orWhere('keyword', 'like', '%' . $monitoringIndexFilters->search . '%');
            });
        }

        if ($monitoringIndexFilters->types !== []) {
            $query->whereIn('type', $monitoringIndexFilters->types);
        }

        if ($monitoringIndexFilters->lifecycleStatus !== null) {
            $query->where('status', $monitoringIndexFilters->lifecycleStatus);
        }

        if ($monitoringIndexFilters->groupId !== null) {
            $query->whereHas('groups', function (Builder $builder) use ($monitoringIndexFilters): void {
                $builder->where('monitoring_groups.id', $monitoringIndexFilters->groupId);
            });
        }

        if ($monitoringIndexFilters->teamId !== null) {
            $query->where('team_id', $monitoringIndexFilters->teamId);
        }

        if ($monitoringIndexFilters->ownership === 'private') {
            $query->whereNull('team_id');
        } elseif ($monitoringIndexFilters->ownership === 'team') {
            $query->whereNotNull('team_id');
        }

        if ($monitoringIndexFilters->healthStatuses !== []) {
            $query->where(function (Builder $builder) use ($monitoringIndexFilters): void {
                if (in_array(MonitoringStatus::UP->value, $monitoringIndexFilters->healthStatuses, true)) {
                    $builder->orWhereHas('latestResponseResult', fn (Builder $builder) => $builder->where('status', MonitoringStatus::UP));
                }

                if (in_array(MonitoringStatus::DOWN->value, $monitoringIndexFilters->healthStatuses, true)) {
                    $builder->orWhereHas('latestResponseResult', fn (Builder $builder) => $builder->where('status', MonitoringStatus::DOWN));
                }

                if (in_array(MonitoringStatus::UNKNOWN->value, $monitoringIndexFilters->healthStatuses, true)) {
                    $builder->orWhereDoesntHave('latestResponseResult')
                        ->orWhereHas('latestResponseResult', fn (Builder $builder) => $builder->where('status', MonitoringStatus::UNKNOWN));
                }
            });
        }

        if ($monitoringIndexFilters->onlyActiveMaintenance) {
            $now = Date::now();
            $query->whereNotNull('maintenance_from')
                ->where('maintenance_from', '<=', $now)
                ->where(function (Builder $builder) use ($now): void {
                    $builder->whereNull('maintenance_until')
                        ->orWhere('maintenance_until', '>=', $now);
                });
        }

        $query->orderBy('status');

        match ($monitoringIndexFilters->sort) {
            'name_desc' => $query->orderByDesc('name'),
            'created_asc' => $query->oldest('monitorings.created_at'),
            'created_desc' => $query->latest('monitorings.created_at'),
            default => $query->orderBy('name'),
        };

        return $query;
    }
}
