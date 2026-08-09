<?php

declare(strict_types=1);

namespace App\Http\Resources\External;

use App\Models\Monitoring;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Monitoring
 */
final class MobileOneOffMaintenanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Monitoring $monitoring */
        $monitoring = $this->resource;

        return [
            'id' => $monitoring->id,
            'kind' => 'one_off',
            'state' => (string) $monitoring->getAttribute('mobile_maintenance_state'),
            'enabled' => true,
            'target' => [
                'type' => 'monitoring',
                'id' => $monitoring->id,
                'name' => $monitoring->name,
                'manageable_monitoring_ids' => $monitoring->getAttribute('mobile_can_manage') ? [(string) $monitoring->id] : [],
            ],
            'schedule' => [
                'starts_at' => $monitoring->maintenance_from?->toIso8601String(),
                'ends_at' => $monitoring->maintenance_until?->toIso8601String(),
                'timezone' => 'UTC',
                'recurrence' => null,
                'duration_minutes' => null,
                'repeat_until' => null,
                'next_occurrence' => null,
            ],
            'can_manage' => (bool) $monitoring->getAttribute('mobile_can_manage'),
            'updated_at' => $monitoring->updated_at?->toIso8601String(),
        ];
    }
}
