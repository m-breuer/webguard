<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StatusPages\UpdateIncidentReviewRequest;
use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StatusPageIncidentReviewController extends Controller
{
    public function update(
        UpdateIncidentReviewRequest $updateIncidentReviewRequest,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($statusPage->user_id === Auth::id(), 404);
        abort_unless($this->incidentBelongsToStatusPage($statusPage, $incident), 404);

        $incident->update($updateIncidentReviewRequest->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_review.messages.updated'));
    }

    private function incidentBelongsToStatusPage(StatusPage $statusPage, Incident $incident): bool
    {
        return $statusPage->components()
            ->whereHas('monitorings', fn ($query) => $query->whereKey($incident->monitoring_id))
            ->exists();
    }
}
