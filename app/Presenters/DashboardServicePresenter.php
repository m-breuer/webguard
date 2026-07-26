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
        return $services->map(fn (MonitoringServiceReadModel $service): array => [
            'id' => $service->id,
            'name' => $service->name,
            'target' => $service->target,
            'status' => $service->status,
            'statusLabel' => (string) __('dashboard.signal_room.statuses.' . $service->status),
            'group' => $service->groupName ?? (string) __('dashboard.signal_room.ungrouped'),
            'lastCheck' => $service->lastCheckedAt !== null
                ? Date::parse($service->lastCheckedAt)->locale(app()->getLocale())->diffForHumans()
                : (string) __('dashboard.signal_room.no_check'),
            'responseTime' => $service->responseTimeMs !== null
                ? number_format($service->responseTimeMs, 0, ',', '.') . ' ms'
                : '—',
            'openIncident' => $service->hasOpenIncident,
            'href' => route('monitorings.show', ['monitoring' => $service->id]),
        ])->values();
    }
}
