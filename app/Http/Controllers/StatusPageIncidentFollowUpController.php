<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IncidentFollowUpStatus;
use App\Http\Controllers\Concerns\InteractsWithStatusPageIncidents;
use App\Http\Requests\StatusPages\StoreIncidentFollowUpRequest;
use App\Http\Requests\StatusPages\UpdateIncidentFollowUpRequest;
use App\Models\Incident;
use App\Models\IncidentFollowUp;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;

class StatusPageIncidentFollowUpController extends Controller
{
    use InteractsWithStatusPageIncidents;

    public function store(
        StoreIncidentFollowUpRequest $request,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        $validated = $request->validated();
        $this->assertAssignedUserIsStatusPageOwner($statusPage, $validated['assigned_user_id'] ?? null);

        $incident->followUps()->create([
            ...$validated,
            'status' => IncidentFollowUpStatus::OPEN,
        ]);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_follow_ups.messages.created'));
    }

    public function update(
        UpdateIncidentFollowUpRequest $request,
        StatusPage $statusPage,
        Incident $incident,
        IncidentFollowUp $followUp
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        abort_unless($followUp->incident_id === $incident->id, 404);

        $validated = $request->validated();
        $this->assertAssignedUserIsStatusPageOwner($statusPage, $validated['assigned_user_id'] ?? null);
        $validated['completed_at'] = $validated['status'] === IncidentFollowUpStatus::COMPLETED->value
            ? ($followUp->completed_at ?? Date::now())
            : null;

        $followUp->update($validated);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_follow_ups.messages.updated'));
    }

    public function destroy(
        StatusPage $statusPage,
        Incident $incident,
        IncidentFollowUp $followUp
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);
        abort_unless($followUp->incident_id === $incident->id, 404);

        $followUp->delete();

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_follow_ups.messages.deleted'));
    }
}
