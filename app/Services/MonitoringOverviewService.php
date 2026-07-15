<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\NotificationChannelDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class MonitoringOverviewService
{
    /**
     * @return array{
     *     monitorings: EloquentCollection<int, Monitoring>,
     *     summary: array{total:int,healthy:int,down:int,unknown:int,paused:int,maintenance:int},
     *     overallState: string,
     *     attentionItems: Collection<int, array{type:string,monitoring:Monitoring|null,count:int|null}>,
     *     recentIncidents: EloquentCollection<int, Incident>,
     *     maintenanceMonitorings: Collection<int, Monitoring>,
     *     trend: list<array{date:string,label:string,uptime_percentage:float|null,has_data:bool}>,
     *     failedDeliveryCount: int,
     *     recommendedAction: string,
     *     canCreateMonitoring: bool,
     *     canManageMaintenance: bool
     * }
     */
    public function overview(User $user): array
    {
        $user->loadMissing('package');

        $monitorings = Monitoring::query()
            ->with([
                'latestResponseResult' => fn ($query) => $query->select([
                    'monitoring_response_results.id',
                    'monitoring_response_results.monitoring_id',
                    'monitoring_response_results.status',
                    'monitoring_response_results.created_at',
                    'monitoring_response_results.updated_at',
                ]),
                'latestIncident' => fn ($query) => $query->select([
                    'incidents.id',
                    'incidents.monitoring_id',
                    'incidents.down_at',
                    'incidents.up_at',
                ]),
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'target',
                'type',
                'status',
                'maintenance_from',
                'maintenance_until',
                'heartbeat_interval_minutes',
                'heartbeat_grace_minutes',
            ]);

        $statuses = $monitorings->mapWithKeys(
            fn (Monitoring $monitoring): array => [$monitoring->id => $this->status($monitoring)]
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

        $attentionItems = $this->attentionItems($monitorings, $failedDeliveryCount);
        $overallState = $this->overallState($summary);

        return [
            'monitorings' => $monitorings,
            'summary' => $summary,
            'overallState' => $overallState,
            'attentionItems' => $attentionItems,
            'recentIncidents' => Incident::query()
                ->with('monitoring')
                ->whereHas('monitoring')
                ->latest('down_at')
                ->limit(5)
                ->get(),
            'maintenanceMonitorings' => $maintenanceMonitorings,
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

    private function status(Monitoring $monitoring): string
    {
        if ($monitoring->isPaused()) {
            return MonitoringLifecycleStatus::PAUSED->value;
        }

        if ($monitoring->isUnderMaintenance()) {
            return 'maintenance';
        }

        $latestIncident = $monitoring->latestIncident;
        if ($latestIncident && $latestIncident->up_at === null) {
            return MonitoringStatus::DOWN->value;
        }

        $latestResponse = $monitoring->latestResponseResult;
        if ($latestResponse === null || $this->isStale($monitoring)) {
            return MonitoringStatus::UNKNOWN->value;
        }

        return $latestResponse->status->value;
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
     * @return Collection<int, array{type:string,monitoring:Monitoring|null,count:int|null}>
     */
    private function attentionItems(EloquentCollection $eloquentCollection, int $failedDeliveryCount): Collection
    {
        $items = collect();

        $eloquentCollection
            ->filter(fn (Monitoring $monitoring): bool => $this->status($monitoring) === MonitoringStatus::DOWN->value)
            ->take(5)
            ->each(function (Monitoring $monitoring) use ($items): void {
                $items->push([
                    'type' => $monitoring->latestIncident?->up_at === null && $monitoring->latestIncident !== null ? 'incident' : 'down',
                    'monitoring' => $monitoring,
                    'count' => null,
                ]);
            });

        $eloquentCollection
            ->filter(fn (Monitoring $monitoring): bool => $this->status($monitoring) === MonitoringStatus::UNKNOWN->value
                && $monitoring->isActive())
            ->take(5)
            ->each(fn (Monitoring $monitoring) => $items->push([
                'type' => $monitoring->latestResponseResult === null ? 'unknown' : 'stale',
                'monitoring' => $monitoring,
                'count' => null,
            ]));

        if ($failedDeliveryCount > 0) {
            $items->push([
                'type' => 'delivery',
                'monitoring' => null,
                'count' => $failedDeliveryCount,
            ]);
        }

        return $items;
    }

    private function isStale(Monitoring $monitoring): bool
    {
        $latestResponse = $monitoring->latestResponseResult;
        if ($latestResponse === null) {
            return false;
        }

        $intervalMinutes = $monitoring->isHeartbeat()
            ? ((int) ($monitoring->heartbeat_interval_minutes ?? 0) + (int) ($monitoring->heartbeat_grace_minutes ?? 0))
            : (int) config('monitoring.interval', 5);

        return $latestResponse->created_at->lt(Date::now()->subMinutes(max(10, $intervalMinutes * 3)));
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
