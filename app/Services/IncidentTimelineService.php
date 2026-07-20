<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class IncidentTimelineService
{
    /**
     * @return Collection<int, array{id: string|null, title: string, description: string|null, occurred_at: Carbon, source_type: string}>
     */
    public function events(Incident $incident): Collection
    {
        $events = collect([
            [
                'id' => null,
                'title' => 'Incident opened',
                'description' => $incident->problem_description,
                'occurred_at' => $incident->down_at,
                'source_type' => 'lifecycle',
            ],
        ]);

        foreach ($incident->updates as $update) {
            $events->push([
                'id' => null,
                'title' => 'Incident update: ' . $update->status->value,
                'description' => $update->message,
                'occurred_at' => $update->created_at,
                'source_type' => 'incident_update',
            ]);
        }

        foreach ($incident->timelineEvents as $event) {
            $events->push([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'occurred_at' => $event->occurred_at,
                'source_type' => $event->source_type,
            ]);
        }

        if ($incident->up_at) {
            $events->push([
                'id' => null,
                'title' => 'Incident resolved',
                'description' => $incident->resolution_description,
                'occurred_at' => $incident->up_at,
                'source_type' => 'lifecycle',
            ]);
        }

        return $events
            ->sortBy('occurred_at')
            ->values();
    }
}
