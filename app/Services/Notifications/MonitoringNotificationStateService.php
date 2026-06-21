<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationState;
use App\Models\User;

class MonitoringNotificationStateService
{
    public function ensureState(MonitoringNotification $monitoringNotification, User $user): MonitoringNotificationState
    {
        /** @var MonitoringNotificationState $monitoringNotificationState */
        $monitoringNotificationState = MonitoringNotificationState::query()->firstOrCreate([
            'monitoring_notification_id' => $monitoringNotification->id,
            'user_id' => $user->id,
        ]);

        return $monitoringNotificationState;
    }

    public function markSent(MonitoringNotification $monitoringNotification, User $user): void
    {
        $this->ensureState($monitoringNotification, $user)->update([
            'sent_at' => now(),
        ]);
    }

    public function markRead(MonitoringNotification $monitoringNotification, User $user): void
    {
        $this->ensureState($monitoringNotification, $user)->update([
            'read_at' => now(),
        ]);
    }
}
