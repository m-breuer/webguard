<?php

declare(strict_types=1);

namespace App\Http\Resources\External;

use App\Models\Monitoring;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Monitoring
 */
final class MobileMonitoringAssignmentResource extends JsonResource
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
            'name' => $monitoring->name,
            'target' => $monitoring->target,
            'type' => $monitoring->type?->value,
            'status' => $monitoring->status?->value,
            'ownership' => [
                'type' => 'private',
                'user_id' => $monitoring->user_id,
                'team_id' => null,
                'can_manage' => true,
            ],
        ];
    }
}
