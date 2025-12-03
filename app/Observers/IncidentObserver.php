<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Models\Incident;
use App\Models\MonitoringNotification;

class IncidentObserver
{
    /**
     * Handle the Incident "created" event.
     */
    public function created(Incident $incident): void
    {
        MonitoringNotification::query()->create([
            'monitoring_id' => $incident->monitoring_id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'DOWN',
            'read' => false,
            'sent' => false,
        ]);
    }

    /**
     * Handle the Incident "updated" event.
     */
    public function updated(Incident $incident): void
    {
        if ($incident->wasChanged('up_at') && $incident->up_at !== null) {
            MonitoringNotification::query()->create([
                'monitoring_id' => $incident->monitoring_id,
                'type' => NotificationType::STATUS_CHANGE,
                'message' => 'UP',
                'read' => false,
                'sent' => false,
            ]);
        }
    }
}
