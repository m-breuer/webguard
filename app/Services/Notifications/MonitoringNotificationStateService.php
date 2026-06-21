<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationState;
use App\Models\User;

class MonitoringNotificationStateService
{
    public function ensureState(MonitoringNotification $notification, User $user): MonitoringNotificationState
    {
        /** @var MonitoringNotificationState $state */
        $state = MonitoringNotificationState::query()->firstOrCreate([
            'monitoring_notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);

        return $state;
    }

    public function markSent(MonitoringNotification $notification, User $user): void
    {
        $this->ensureState($notification, $user)->update([
            'sent_at' => now(),
        ]);
    }

    public function markRead(MonitoringNotification $notification, User $user): void
    {
        $this->ensureState($notification, $user)->update([
            'read_at' => now(),
        ]);
    }
}
