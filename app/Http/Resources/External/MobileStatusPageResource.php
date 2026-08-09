<?php

declare(strict_types=1);

namespace App\Http\Resources\External;

use App\Models\Monitoring;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StatusPage
 */
final class MobileStatusPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StatusPage $statusPage */
        $statusPage = $this->resource;

        return [
            'id' => $statusPage->id,
            'name' => $statusPage->name,
            'description' => $statusPage->description,
            'publication' => [
                'is_public' => $statusPage->is_public,
                'can_change' => true,
            ],
            'component_count' => (int) ($statusPage->components_count ?? 0),
            'verified_subscriber_count' => (int) ($statusPage->verified_subscriber_count ?? 0),
            'open_incident_count' => (int) ($statusPage->open_incident_count ?? 0),
            'components' => $statusPage->relationLoaded('components')
                ? $statusPage->components
                    ->map(fn (StatusPageComponent $component): array => $this->componentPayload($component))
                    ->values()
                    ->all()
                : [],
            'created_at' => $statusPage->created_at?->toIso8601String(),
            'updated_at' => $statusPage->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function componentPayload(StatusPageComponent $component): array
    {
        return [
            'id' => $component->id,
            'name' => $component->name,
            'description' => $component->description,
            'position' => $component->position,
            'source_type' => $component->source_type->value,
            'monitoring_group' => $component->relationLoaded('monitoringGroup') && $component->monitoringGroup !== null
                ? [
                    'id' => $component->monitoringGroup->id,
                    'name' => $component->monitoringGroup->name,
                    'monitoring_count' => (int) ($component->monitoringGroup->monitorings_count ?? 0),
                ]
                : null,
            'monitorings' => $this->componentMonitorings($component)
                ->map(fn (Monitoring $monitoring): array => [
                    'id' => $monitoring->id,
                    'name' => $monitoring->name,
                    'target' => $monitoring->target,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Monitoring>
     */
    private function componentMonitorings(StatusPageComponent $component): \Illuminate\Support\Collection
    {
        if ($component->source_type->value === 'monitoring_group') {
            return $component->monitoringGroup?->monitorings ?? collect();
        }

        return $component->monitorings;
    }
}
