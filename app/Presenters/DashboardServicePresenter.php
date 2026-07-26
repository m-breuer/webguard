<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Data\MonitoringServiceReadModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class DashboardServicePresenter
{
    /**
     * @param  Collection<int, MonitoringServiceReadModel>  $services
     * @return Collection<int, array{id:string,name:string,target:string,status:string,statusLabel:string,group:string,lastCheck:string,responseTime:string,openIncident:bool,href:string}>
     */
    public function present(Collection $services): Collection
    {
        return $services->map(fn (MonitoringServiceReadModel $monitoringServiceReadModel): array => [
            'id' => $monitoringServiceReadModel->id,
            'name' => $monitoringServiceReadModel->name,
            'target' => $monitoringServiceReadModel->target,
            'status' => $monitoringServiceReadModel->status,
            'statusLabel' => (string) __('dashboard.signal_room.statuses.' . $monitoringServiceReadModel->status),
            'group' => $monitoringServiceReadModel->groupName ?? (string) __('dashboard.signal_room.ungrouped'),
            'lastCheck' => $monitoringServiceReadModel->lastCheckedAt !== null
                ? Date::parse($monitoringServiceReadModel->lastCheckedAt)->locale(app()->getLocale())->diffForHumans()
                : (string) __('dashboard.signal_room.no_check'),
            'responseTime' => $monitoringServiceReadModel->responseTimeMs !== null
                ? number_format($monitoringServiceReadModel->responseTimeMs, 0, ',', '.') . ' ms'
                : '—',
            'openIncident' => $monitoringServiceReadModel->hasOpenIncident,
            'href' => route('monitorings.show', ['monitoring' => $monitoringServiceReadModel->id]),
        ])->values();
    }
}
