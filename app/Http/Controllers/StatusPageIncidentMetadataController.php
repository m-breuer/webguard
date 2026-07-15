<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithStatusPageIncidents;
use App\Http\Requests\StatusPages\UpdateIncidentMetadataRequest;
use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;

class StatusPageIncidentMetadataController extends Controller
{
    use InteractsWithStatusPageIncidents;

    public function update(
        UpdateIncidentMetadataRequest $updateIncidentMetadataRequest,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);

        $incident->update($updateIncidentMetadataRequest->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_metadata.messages.updated'));
    }
}
