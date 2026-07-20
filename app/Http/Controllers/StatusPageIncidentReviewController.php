<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithStatusPageIncidents;
use App\Http\Requests\StatusPages\UpdateIncidentReviewRequest;
use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;

class StatusPageIncidentReviewController extends Controller
{
    use InteractsWithStatusPageIncidents;

    public function update(
        UpdateIncidentReviewRequest $updateIncidentReviewRequest,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        $this->authorizeIncident($statusPage, $incident);

        $incident->update($updateIncidentReviewRequest->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_review.messages.updated'));
    }
}
