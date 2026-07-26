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
    public function for(User $user, MonitoringIndexFilters $filters, int $perPage = 5): MonitoringIndexReadModel
    {
        $query = $this->query($user, $filters);
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
    private function query(User $user, MonitoringIndexFilters $filters): Builder
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

        if ($filters->search !== null) {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->where('name', 'like', '%' . $filters->search . '%')
                    ->orWhere('target', 'like', '%' . $filters->search . '%')
                    ->orWhere('port', 'like', '%' . $filters->search . '%')
                    ->orWhere('keyword', 'like', '%' . $filters->search . '%');
            });
        }

        if ($filters->types !== []) {
            $query->whereIn('type', $filters->types);
        }

        if ($filters->lifecycleStatus !== null) {
            $query->where('status', $filters->lifecycleStatus);
        }

        if ($filters->groupId !== null) {
            $query->whereHas('groups', function (Builder $builder) use ($filters): void {
                $builder->where('monitoring_groups.id', $filters->groupId);
            });
        }

        if ($filters->teamId !== null) {
            $query->where('team_id', $filters->teamId);
        }

        if ($filters->ownership === 'private') {
            $query->whereNull('team_id');
        } elseif ($filters->ownership === 'team') {
            $query->whereNotNull('team_id');
        }

        if ($filters->healthStatuses !== []) {
            $query->where(function (Builder $builder) use ($filters): void {
                if (in_array(MonitoringStatus::UP->value, $filters->healthStatuses, true)) {
                    $builder->orWhereHas('latestResponseResult', fn (Builder $query) => $query->where('status', MonitoringStatus::UP));
                }

                if (in_array(MonitoringStatus::DOWN->value, $filters->healthStatuses, true)) {
                    $builder->orWhereHas('latestResponseResult', fn (Builder $query) => $query->where('status', MonitoringStatus::DOWN));
                }

                if (in_array(MonitoringStatus::UNKNOWN->value, $filters->healthStatuses, true)) {
                    $builder->orWhereDoesntHave('latestResponseResult')
                        ->orWhereHas('latestResponseResult', fn (Builder $query) => $query->where('status', MonitoringStatus::UNKNOWN));
                }
            });
        }

        if ($filters->onlyActiveMaintenance) {
            $now = Date::now();
            $query->whereNotNull('maintenance_from')
                ->where('maintenance_from', '<=', $now)
                ->where(function (Builder $builder) use ($now): void {
                    $builder->whereNull('maintenance_until')
                        ->orWhere('maintenance_until', '>=', $now);
                });
        }

        $query->orderBy('status');

        match ($filters->sort) {
            'name_desc' => $query->orderByDesc('name'),
            'created_asc' => $query->oldest('monitorings.created_at'),
            'created_desc' => $query->latest('monitorings.created_at'),
            default => $query->orderBy('name'),
        };

        return $query;
    }
}
