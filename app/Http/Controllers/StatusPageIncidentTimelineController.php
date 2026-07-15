<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithStatusPageIncidents;
use App\Http\Requests\StatusPages\StoreIncidentTimelineEventRequest;
use App\Http\Requests\StatusPages\UpdateIncidentTimelineEventRequest;
use App\Models\Incident;
use App\Models\IncidentTimelineEvent;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;

class StatusPageIncidentTimelineController extends Controller
{
    use InteractsWithStatusPageIncidents;

    public function store(
        StoreIncidentTimelineEventRequest $request,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);

        $incident->timelineEvents()->create([
            ...$request->validated(),
            'source_type' => 'custom',
        ]);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_timeline.messages.created'));
    }

    public function update(
        UpdateIncidentTimelineEventRequest $request,
        StatusPage $statusPage,
        Incident $incident,
        IncidentTimelineEvent $timelineEvent
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        abort_unless($timelineEvent->incident_id === $incident->id, 404);

        $timelineEvent->update($request->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_timeline.messages.updated'));
    }

    public function destroy(
        StatusPage $statusPage,
        Incident $incident,
        IncidentTimelineEvent $timelineEvent
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        abort_unless($timelineEvent->incident_id === $incident->id, 404);

        $timelineEvent->delete();

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_timeline.messages.deleted'));
    }
}
