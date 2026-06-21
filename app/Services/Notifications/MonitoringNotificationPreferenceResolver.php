<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Monitoring;
use App\Models\MonitoringNotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class MonitoringNotificationPreferenceResolver
{
    public function preferenceFor(Monitoring $monitoring, User $user): MonitoringNotificationPreference
    {
        /** @var MonitoringNotificationPreference $monitoringNotificationPreference */
        $monitoringNotificationPreference = MonitoringNotificationPreference::query()->firstOrCreate(
            [
                'monitoring_id' => $monitoring->id,
                'user_id' => $user->id,
            ],
            [
                'notification_on_failure' => $monitoring->notification_on_failure,
                'notification_channels' => $monitoring->user_id === $user->id
                    ? $monitoring->notification_channels
                    : $user->enabledNotificationChannelKeys(),
                'ssl_expiry_warning_days' => $monitoring->ssl_expiry_warning_days ?? 7,
            ]
        );

        return $monitoringNotificationPreference;
    }

    /**
     * @return Collection<int, array{user: User, preference: MonitoringNotificationPreference}>
     */
    public function recipientsFor(Monitoring $monitoring): Collection
    {
        $users = $this->usersFor($monitoring);

        return $users
            ->map(fn (User $user): array => [
                'user' => $user,
                'preference' => $this->preferenceFor($monitoring, $user),
            ])
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function usersFor(Monitoring $monitoring): Collection
    {
        if ($monitoring->team_id !== null) {
            $team = $monitoring->team()->with('users')->first();

            if (! $team) {
                return collect();
            }

            /** @var EloquentCollection<int, User> $users */
            $users = $team->users;

            return $users->toBase();
        }

        $user = $monitoring->user;

        return $user ? collect([$user]) : collect();
    }
}
