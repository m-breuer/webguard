<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithStatusPageIncidents;
use App\Http\Requests\StatusPages\StoreIncidentUpdateRequest;
use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;

class StatusPageIncidentUpdateController extends Controller
{
    use InteractsWithStatusPageIncidents;

    public function store(
        StoreIncidentUpdateRequest $storeIncidentUpdateRequest,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);

        $incident->updates()->create($storeIncidentUpdateRequest->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_updates.messages.created'));
    }
}
