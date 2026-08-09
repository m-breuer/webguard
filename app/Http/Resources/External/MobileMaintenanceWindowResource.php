<?php

declare(strict_types=1);

namespace App\Http\Resources\External;

use App\Models\MaintenanceWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MaintenanceWindow
 */
final class MobileMaintenanceWindowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MaintenanceWindow $maintenanceWindow */
        $maintenanceWindow = $this->resource;

        return [
            'id' => $maintenanceWindow->id,
            'kind' => 'recurring',
            'state' => (string) $maintenanceWindow->getAttribute('mobile_state'),
            'enabled' => $maintenanceWindow->enabled,
            'target' => [
                'type' => $maintenanceWindow->monitoring_id === null ? 'monitoring_group' : 'monitoring',
                'id' => $maintenanceWindow->monitoring_id ?? $maintenanceWindow->monitoring_group_id,
                'name' => $maintenanceWindow->monitoring?->name ?? $maintenanceWindow->monitoringGroup?->name,
                'manageable_monitoring_ids' => $maintenanceWindow->getAttribute('manageable_monitoring_ids'),
            ],
            'schedule' => [
                'starts_at' => $maintenanceWindow->starts_at->toIso8601String(),
                'ends_at' => $maintenanceWindow->getAttribute('mobile_ends_at'),
                'timezone' => $maintenanceWindow->timezone,
                'recurrence' => $maintenanceWindow->recurrence->value,
                'duration_minutes' => $maintenanceWindow->duration_minutes,
                'repeat_until' => $maintenanceWindow->repeat_until?->toIso8601String(),
                'next_occurrence' => $maintenanceWindow->getAttribute('mobile_next_occurrence'),
            ],
            'can_manage' => (bool) $maintenanceWindow->getAttribute('mobile_can_manage'),
            'updated_at' => $maintenanceWindow->updated_at?->toIso8601String(),
        ];
    }
}
