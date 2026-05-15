<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StatusPages\StoreIncidentUpdateRequest;
use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StatusPageIncidentUpdateController extends Controller
{
    public function store(
        StoreIncidentUpdateRequest $storeIncidentUpdateRequest,
        StatusPage $statusPage,
        Incident $incident
    ): RedirectResponse {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($statusPage->user_id === Auth::id(), 404);
        abort_unless($this->incidentBelongsToStatusPage($statusPage, $incident), 404);

        $incident->updates()->create($storeIncidentUpdateRequest->validated());

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.incident_updates.messages.created'));
    }

    private function incidentBelongsToStatusPage(StatusPage $statusPage, Incident $incident): bool
    {
        return $statusPage->components()
            ->whereHas('monitorings', fn ($query) => $query->whereKey($incident->monitoring_id))
            ->exists();
    }
}
