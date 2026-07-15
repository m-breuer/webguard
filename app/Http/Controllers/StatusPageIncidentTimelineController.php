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
        StoreIncidentTimelineEventRequest $storeIncidentTimelineEventRequest,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);

        $incident->timelineEvents()->create([
            ...$storeIncidentTimelineEventRequest->validated(),
            'source_type' => 'custom',
        ]);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_timeline.messages.created'));
    }

    public function update(
        UpdateIncidentTimelineEventRequest $updateIncidentTimelineEventRequest,
        StatusPage $statusPage,
        Incident $incident,
        IncidentTimelineEvent $incidentTimelineEvent
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        abort_unless($incidentTimelineEvent->incident_id === $incident->id, 404);

        $incidentTimelineEvent->update($updateIncidentTimelineEventRequest->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_timeline.messages.updated'));
    }

    public function destroy(
        StatusPage $statusPage,
        Incident $incident,
        IncidentTimelineEvent $incidentTimelineEvent
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        abort_unless($incidentTimelineEvent->incident_id === $incident->id, 404);

        $incidentTimelineEvent->delete();

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_timeline.messages.deleted'));
    }
}
