<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MonitoringServiceReadModel;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\StatusPageComponentSource;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\NotificationChannelDelivery;
use App\Models\StatusPage;
use App\Models\User;
use App\Queries\MonitoringOverviewQuery;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringOverviewService
{
    public function __construct(
        private readonly MonitoringOverviewQuery $monitoringOverviewQuery,
        private readonly MonitoringStateResolver $monitoringStateResolver,
    ) {}

    /**
     * @return array{
     *     monitorings: EloquentCollection<int, Monitoring>,
     *     signalRoomServices: Collection<int, array{id:string,name:string,target:string,status:string,statusLabel:string,group:string,lastCheck:string,responseTime:string,openIncident:bool,href:string}>,
     *     serviceReadModels: Collection<int, MonitoringServiceReadModel>,
     *     signalRoomPagination: array{current_page:int,last_page:int,total:int,from:int|null,to:int|null},
     *     summary: array{total:int,healthy:int,down:int,unknown:int,paused:int,maintenance:int},
     *     overallState: string,
     *     attentionItems: Collection<int, array{type:string,monitoring:Monitoring|null,count:int|null}>,
     *     recentIncidents: EloquentCollection<int, Incident>,
     *     maintenanceMonitorings: Collection<int, Monitoring>,
     *     statusPages: EloquentCollection<int, StatusPage>,
     *     trend: list<array{date:string,label:string,uptime_percentage:float|null,has_data:bool}>,
     *     failedDeliveryCount: int,
     *     recommendedAction: string,
     *     canCreateMonitoring: bool,
     *     canManageMaintenance: bool
     * }
     */
    public function overview(User $user, int $servicePage = 1): array
    {
        $user->loadMissing('package');

        $monitorings = $this->monitoringOverviewQuery->monitoringsFor($user);

        $statuses = $monitorings->mapWithKeys(
            fn (Monitoring $monitoring): array => [$monitoring->id => $this->monitoringStateResolver->status($monitoring)]
        );
        $summary = [
            'total' => $monitorings->count(),
            'healthy' => $statuses->filter(fn (string $status): bool => $status === MonitoringStatus::UP->value)->count(),
            'down' => $statuses->filter(fn (string $status): bool => $status === MonitoringStatus::DOWN->value)->count(),
            'unknown' => $statuses->filter(fn (string $status): bool => $status === MonitoringStatus::UNKNOWN->value)->count(),
            'paused' => $statuses->filter(fn (string $status): bool => $status === MonitoringLifecycleStatus::PAUSED->value)->count(),
            'maintenance' => $statuses->filter(fn (string $status): bool => $status === 'maintenance')->count(),
        ];

        $failedDeliveryCount = NotificationChannelDelivery::query()
            ->where('user_id', $user->id)
            ->where('status', NotificationDeliveryStatus::FAILED)
            ->where('created_at', '>=', Date::now()->subDays(7))
            ->count();

        $maintenanceMonitorings = $monitorings
            ->filter(fn (Monitoring $monitoring): bool => $monitoring->maintenance_from !== null
                && ($monitoring->isUnderMaintenance() || $monitoring->maintenance_from->isFuture()))
            ->sortBy('maintenance_from')
            ->values();

        $attentionItems = $this->attentionItems(
            $monitorings,
            $failedDeliveryCount,
            $this->statusPagesByMonitoringId($user)
        );
        $overallState = $this->overallState($summary);
        $serviceReadModels = $monitorings->map(
            fn (Monitoring $monitoring): MonitoringServiceReadModel => MonitoringServiceReadModel::fromMonitoring(
                $monitoring,
                (string) $statuses->get($monitoring->getKey(), MonitoringStatus::UNKNOWN->value),
            )
        )->values();
        $signalRoomServices = $serviceReadModels->map(
            fn (MonitoringServiceReadModel $service): array => $this->servicePresentation($service)
        );
        $lengthAwarePaginator = new LengthAwarePaginator(
            $signalRoomServices->forPage(max(1, $servicePage), 10)->values(),
            $signalRoomServices->count(),
            10,
            max(1, $servicePage),
            ['pageName' => 'service_page', 'path' => route('dashboard', absolute: false)],
        );

        return [
            'monitorings' => $monitorings,
            'signalRoomServices' => $lengthAwarePaginator->getCollection(),
            'serviceReadModels' => $serviceReadModels->forPage(max(1, $servicePage), 10)->values(),
            'signalRoomPagination' => [
                'current_page' => $lengthAwarePaginator->currentPage(),
                'last_page' => $lengthAwarePaginator->lastPage(),
                'total' => $lengthAwarePaginator->total(),
                'from' => $lengthAwarePaginator->firstItem(),
                'to' => $lengthAwarePaginator->lastItem(),
            ],
            'summary' => $summary,
            'overallState' => $overallState,
            'attentionItems' => $attentionItems,
            'recentIncidents' => Incident::query()
                ->with('monitoring')
                ->whereHas('monitoring', fn ($query) => $query->visibleTo($user))
                ->latest('down_at')
                ->limit(5)
                ->get(),
            'maintenanceMonitorings' => $maintenanceMonitorings,
            'statusPages' => $user->statusPages()
                ->withCount('components')
                ->latest()
                ->limit(4)
                ->get(),
            'trend' => $this->trend($monitorings),
            'failedDeliveryCount' => $failedDeliveryCount,
            'recommendedAction' => $this->recommendedAction($summary, $failedDeliveryCount, $maintenanceMonitorings->isNotEmpty()),
            'canCreateMonitoring' => ! $user->isDemo()
                && (($user->monitorings()->whereNull('team_id')->count() < (int) ($user->package?->monitoring_limit ?? 0))
                    || $user->administeredTeams()->exists()),
            'canManageMaintenance' => ! $user->isDemo()
                && Monitoring::query()->manageableBy($user)->exists(),
        ];
    }

    /**
     * Load only the bounded service-map page used by async dashboard navigation.
     *
     * @return array{services:Collection<int, array{id:string,name:string,target:string,status:string,statusLabel:string,group:string,lastCheck:string,responseTime:string,openIncident:bool,href:string}>,pagination:array{current_page:int,last_page:int,total:int,from:int|null,to:int|null},total:int}
     */
    public function serviceMap(User $user, int $servicePage = 1): array
    {
        $servicePaginator = $this->monitoringOverviewQuery->paginateServicesFor($user, $servicePage);

        $services = $this->mapSignalRoomServices($servicePaginator->getCollection());

        return [
            'services' => $services,
            'pagination' => [
                'current_page' => $servicePaginator->currentPage(),
                'last_page' => $servicePaginator->lastPage(),
                'total' => $servicePaginator->total(),
                'from' => $servicePaginator->firstItem(),
                'to' => $servicePaginator->lastItem(),
            ],
            'total' => $servicePaginator->total(),
        ];
    }

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     * @return Collection<int, array{id:string,name:string,target:string,status:string,statusLabel:string,group:string,lastCheck:string,responseTime:string,openIncident:bool,href:string}>
     */
    private function mapSignalRoomServices(Collection $monitorings): Collection
    {
        $statuses = $monitorings->mapWithKeys(
            fn (Monitoring $monitoring): array => [$monitoring->id => $this->monitoringStateResolver->status($monitoring)]
        );

        return $monitorings->map(
            fn (Monitoring $monitoring): array => $this->servicePresentation(
                MonitoringServiceReadModel::fromMonitoring(
                    $monitoring,
                    (string) $statuses->get($monitoring->getKey(), MonitoringStatus::UNKNOWN->value),
                )
            )
        )->values();
    }

    /**
     * @return array{id:string,name:string,target:string,status:string,statusLabel:string,group:string,lastCheck:string,responseTime:string,openIncident:bool,href:string}
     */
    private function servicePresentation(MonitoringServiceReadModel $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'target' => $service->target,
            'status' => $service->status,
            'statusLabel' => (string) __('dashboard.signal_room.statuses.' . $service->status),
            'group' => $service->groupName ?? (string) __('dashboard.signal_room.ungrouped'),
            'lastCheck' => $service->lastCheckedAt !== null
                ? Carbon::parse($service->lastCheckedAt)->locale(app()->getLocale())->diffForHumans()
                : (string) __('dashboard.signal_room.no_check'),
            'responseTime' => $service->responseTimeMs !== null
                ? number_format($service->responseTimeMs, 0, ',', '.') . ' ms'
                : '—',
            'openIncident' => $service->hasOpenIncident,
            'href' => route('monitorings.show', ['monitoring' => $service->id]),
        ];
    }

    /**
     * @param  array{total:int,healthy:int,down:int,unknown:int,paused:int,maintenance:int}  $summary
     */
    private function overallState(array $summary): string
    {
        if ($summary['total'] === 0) {
            return 'new';
        }

        if ($summary['down'] > 0) {
            return 'degraded';
        }

        if ($summary['unknown'] > 0) {
            return 'attention';
        }

        return 'healthy';
    }

    /**
     * @param  array{total:int,healthy:int,down:int,unknown:int,paused:int,maintenance:int}  $summary
     */
    private function recommendedAction(array $summary, int $failedDeliveryCount, bool $hasMaintenance): string
    {
        if ($summary['total'] === 0) {
            return 'create';
        }

        if ($summary['down'] > 0) {
            return 'incidents';
        }

        if ($summary['unknown'] > 0) {
            return 'unknown';
        }

        if ($failedDeliveryCount > 0) {
            return 'notifications';
        }

        if ($hasMaintenance) {
            return 'maintenance';
        }

        return 'monitorings';
    }

    /**
     * @param  EloquentCollection<int, Monitoring>  $eloquentCollection
     * @param  Collection<string, StatusPage>  $statusPagesByMonitoringId
     * @return Collection<int, array{type:string,monitoring:Monitoring|null,count:int|null,statusPage:StatusPage|null}>
     */
    private function attentionItems(
        EloquentCollection $eloquentCollection,
        int $failedDeliveryCount,
        Collection $statusPagesByMonitoringId
    ): Collection {
        $items = collect();

        $eloquentCollection
            ->filter(fn (Monitoring $monitoring): bool => $this->monitoringStateResolver->status($monitoring) === MonitoringStatus::DOWN->value)
            ->take(5)
            ->each(function (Monitoring $monitoring) use ($items, $statusPagesByMonitoringId): void {
                $isOpenIncident = $monitoring->latestIncident?->up_at === null
                    && $monitoring->latestIncident !== null;

                $items->push([
                    'type' => $isOpenIncident ? 'incident' : 'down',
                    'monitoring' => $monitoring,
                    'count' => null,
                    'statusPage' => $isOpenIncident ? $statusPagesByMonitoringId->get($monitoring->id) : null,
                ]);
            });

        $eloquentCollection
            ->filter(fn (Monitoring $monitoring): bool => $this->monitoringStateResolver->status($monitoring) === MonitoringStatus::UNKNOWN->value
                && $monitoring->isActive())
            ->take(5)
            ->each(fn (Monitoring $monitoring) => $items->push([
                'type' => $monitoring->latestResponseResult === null ? 'unknown' : 'stale',
                'monitoring' => $monitoring,
                'count' => null,
                'statusPage' => null,
            ]));

        if ($failedDeliveryCount > 0) {
            $items->push([
                'type' => 'delivery',
                'monitoring' => null,
                'count' => $failedDeliveryCount,
                'statusPage' => null,
            ]);
        }

        return $items;
    }

    /**
     * @return Collection<string, StatusPage>
     */
    private function statusPagesByMonitoringId(User $user): Collection
    {
        $statusPages = $user->statusPages()->with([
            'components.monitorings',
            'components.monitoringGroup.monitorings',
        ])->get();
        $statusPagesByMonitoringId = collect();

        foreach ($statusPages as $statusPage) {
            foreach ($statusPage->components as $component) {
                $monitorings = $component->source_type === StatusPageComponentSource::MONITORING_GROUP
                    ? $component->monitoringGroup?->monitorings ?? new EloquentCollection()
                    : $component->monitorings;

                foreach ($monitorings as $monitoring) {
                    $statusPagesByMonitoringId->put($monitoring->id, $statusPage);
                }
            }
        }

        return $statusPagesByMonitoringId;
    }

    /**
     * @param  EloquentCollection<int, Monitoring>  $eloquentCollection
     * @return list<array{date:string,label:string,uptime_percentage:float|null,has_data:bool}>
     */
    private function trend(EloquentCollection $eloquentCollection): array
    {
        $dates = collect(range(6, 0))->map(fn (int $days): Carbon => Date::now()->subDays($days)->startOfDay());
        $dailyResults = $eloquentCollection->isEmpty()
            ? collect()
            : MonitoringDailyResult::query()
                ->whereIn('monitoring_id', $eloquentCollection->modelKeys())
                ->whereBetween('date', [$dates->last()->toDateString(), $dates->first()->toDateString()])
                ->get(['date', 'uptime_minutes', 'downtime_minutes', 'unknown_minutes'])
                ->groupBy(fn (MonitoringDailyResult $monitoringDailyResult): string => $monitoringDailyResult->date->toDateString());

        return $dates->map(function (Carbon $date) use ($dailyResults): array {
            $rows = $dailyResults->get($date->toDateString(), collect());
            $uptimeMinutes = (int) $rows->sum('uptime_minutes');
            $downtimeMinutes = (int) $rows->sum('downtime_minutes');
            $unknownMinutes = (int) $rows->sum('unknown_minutes');
            $trackedMinutes = $uptimeMinutes + $downtimeMinutes + $unknownMinutes;

            return [
                'date' => $date->toDateString(),
                'label' => $date->locale(app()->getLocale())->isoFormat('ddd'),
                'uptime_percentage' => $trackedMinutes > 0 ? round(($uptimeMinutes / $trackedMinutes) * 100, 2) : null,
                'has_data' => $trackedMinutes > 0,
            ];
        })->all();
    }
}
