<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Support\Facades\Auth;

trait InteractsWithStatusPageIncidents
{
    protected function authorizeIncident(StatusPage $statusPage, Incident $incident): void
    {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($statusPage->user_id === Auth::id(), 404);
        abort_unless($this->incidentBelongsToStatusPage($statusPage, $incident), 404);
    }

    protected function incidentBelongsToStatusPage(StatusPage $statusPage, Incident $incident): bool
    {
        return $statusPage->components()
            ->where(function ($query) use ($incident): void {
                $query->whereHas('monitorings', fn ($query) => $query->whereKey($incident->monitoring_id))
                    ->orWhereHas('monitoringGroup.monitorings', fn ($query) => $query->whereKey($incident->monitoring_id));
            })
            ->exists();
    }

    protected function assertAssignedUserIsStatusPageOwner(StatusPage $statusPage, ?string $assignedUserId): void
    {
        abort_unless($assignedUserId === null || $assignedUserId === $statusPage->user_id, 422);
    }
}
