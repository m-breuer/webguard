<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\StatusPage;
use App\Models\User;

final class OperationsOverviewPayloadService
{
    public function __construct(
        private readonly MonitoringOverviewService $monitoringOverviewService
    ) {}

    /**
     * @return array{data: array<string, mixed>, service_pagination: array{current_page:int,last_page:int,total:int,from:int|null,to:int|null}}
     */
    public function for(User $user, int $servicePage = 1): array
    {
        $overview = $this->monitoringOverviewService->overview($user, $servicePage);
        $monitorings = $overview['monitorings']->keyBy(
            fn (Monitoring $monitoring): string => (string) $monitoring->getKey()
        );

        return [
            'data' => [
                'overall_state' => $overview['overallState'],
                'summary' => $overview['summary'],
                'services' => $overview['signalRoomServices']->map(
                    fn (array $service): array => $this->servicePayload($service, $monitorings->get($service['id']))
                )->values()->all(),
                'attention' => $overview['attentionItems']->map(
                    fn (array $item): array => $this->attentionPayload($item)
                )->values()->all(),
                'maintenance' => $overview['maintenanceMonitorings']->map(
                    fn (Monitoring $monitoring): array => $this->maintenancePayload($monitoring)
                )->values()->all(),
                'recent_incidents' => $overview['recentIncidents']->map(
                    fn (Incident $incident): array => $this->incidentPayload($incident)
                )->values()->all(),
                'trend' => $overview['trend'],
                'failed_delivery_count' => $overview['failedDeliveryCount'],
                'recommended_action' => $overview['recommendedAction'],
                'capabilities' => [
                    'can_create_monitoring' => $overview['canCreateMonitoring'],
                    'can_manage_maintenance' => $overview['canManageMaintenance'],
                ],
            ],
            'service_pagination' => $overview['signalRoomPagination'],
        ];
    }

    /**
     * @param  array{id:string,name:string,target:string,status:string,statusLabel:string,group:string,lastCheck:string,responseTime:string,openIncident:bool,href:string}  $service
     * @return array<string, mixed>
     */
    private function servicePayload(array $service, ?Monitoring $monitoring): array
    {
        $latestResponse = $monitoring?->latestResponseResult;

        return [
            'id' => $service['id'],
            'name' => $service['name'],
            'target' => $service['target'],
            'type' => $monitoring?->type?->value ?? $monitoring?->type,
            'group' => $service['group'],
            'status' => $service['status'],
            'open_incident' => $service['openIncident'],
            'last_checked_at' => $latestResponse?->created_at?->toIso8601String(),
            'response_time_ms' => $latestResponse?->response_time,
        ];
    }

    /**
     * @param  array{type:string,monitoring:Monitoring|null,count:int|null,statusPage:StatusPage|null}  $item
     * @return array<string, mixed>
     */
    private function attentionPayload(array $item): array
    {
        $monitoring = $item['monitoring'];
        $statusPage = $item['statusPage'];

        return [
            'type' => $item['type'],
            'count' => $item['count'],
            'monitoring_id' => $monitoring?->getKey(),
            'monitoring_name' => $monitoring?->name,
            'monitoring_target' => $monitoring?->target,
            'status_page_id' => $statusPage?->getKey(),
            'status_page_name' => $statusPage?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenancePayload(Monitoring $monitoring): array
    {
        return [
            'monitoring_id' => $monitoring->getKey(),
            'monitoring_name' => $monitoring->name,
            'monitoring_target' => $monitoring->target,
            'status' => $monitoring->isUnderMaintenance() ? 'active' : 'upcoming',
            'starts_at' => $monitoring->maintenance_from?->toIso8601String(),
            'ends_at' => $monitoring->maintenance_until?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incidentPayload(Incident $incident): array
    {
        return [
            'id' => $incident->getKey(),
            'monitoring_id' => $incident->monitoring?->getKey(),
            'monitoring_name' => $incident->monitoring?->name,
            'monitoring_target' => $incident->monitoring?->target,
            'down_at' => $incident->down_at?->toIso8601String(),
            'up_at' => $incident->up_at?->toIso8601String(),
            'resolved' => $incident->up_at !== null,
        ];
    }
}
