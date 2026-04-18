<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Support\MonitoringStatusMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationBoardService
{
    /**
     * @return Collection<int, array{
     *     notification_id: string,
     *     monitoring_id: string,
     *     monitor_name: string,
     *     target: string,
     *     type: string,
     *     latest_status_code: int|null,
     *     latest_checked_at: string|null,
     *     latest_status_change_at: string|null,
     *     status_identifier: string,
     *     status_key: string,
     *     status_change_key: string,
     *     badge_type: string,
     *     read: bool
     * }>
     */
    public function getStatusBoardEntries(bool $showRead, int $offset = 0, int $limit = 5): Collection
    {
        $statusNotificationRelation = $showRead
            ? 'latestStatusChangeNotification'
            : 'latestUnreadStatusChangeNotification';

        $latestStatusChangeAlias = $showRead
            ? 'latest_status_change_at'
            : 'latest_unread_status_change_at';

        $monitorings = Monitoring::query()
            ->select(['id', 'name', 'target', 'type', 'maintenance_from', 'maintenance_until'])
            ->whereHas($statusNotificationRelation)
            ->with([
                $statusNotificationRelation,
                'latestResponseResult',
            ])
            ->withMax([
                "notifications as {$latestStatusChangeAlias}" => function (Builder $builder) use ($showRead): void {
                    $builder->statusChange();

                    if (! $showRead) {
                        $builder->unread();
                    }
                },
            ], 'created_at')
            ->orderByDesc($latestStatusChangeAlias)
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        return $monitorings->map(function (Monitoring $monitoring) use ($statusNotificationRelation): array {
            /** @var MonitoringNotification $statusNotification */
            $statusNotification = $monitoring->{$statusNotificationRelation};
            $latestResponse = $monitoring->latestResponseResult;
            $maintenanceActive = $monitoring->isUnderMaintenance();

            $statusIdentifier = MonitoringStatusMeta::identifier(
                $latestResponse?->http_status_code,
                $maintenanceActive
            );

            return [
                'notification_id' => $statusNotification->id,
                'monitoring_id' => $monitoring->id,
                'monitor_name' => $monitoring->name,
                'target' => $monitoring->target,
                'type' => $monitoring->type->value,
                'latest_status_code' => $latestResponse?->http_status_code,
                'latest_checked_at' => $latestResponse?->created_at?->toIso8601String(),
                'latest_status_change_at' => $statusNotification->created_at?->toIso8601String(),
                'status_identifier' => MonitoringStatusMeta::statusIdentifier(
                    $latestResponse?->http_status_code,
                    $maintenanceActive
                ),
                'status_key' => MonitoringStatusMeta::statusKey(
                    $latestResponse?->http_status_code,
                    $maintenanceActive
                ),
                'status_change_key' => $statusNotification->statusChangeKey($maintenanceActive),
                'badge_type' => MonitoringStatusMeta::badgeType($statusIdentifier),
                'read' => (bool) $statusNotification->read,
            ];
        });
    }

    public function getUnreadNotificationCount(): int
    {
        $baseUnreadNotificationsQuery = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitorings.user_id', auth()->id())
            ->whereNull('monitorings.deleted_at')
            ->where('monitoring_notifications.read', false);

        $unreadStatusChangeCount = (clone $baseUnreadNotificationsQuery)
            ->where('monitoring_notifications.type', NotificationType::STATUS_CHANGE->value)
            ->distinct('monitoring_notifications.monitoring_id')
            ->count('monitoring_notifications.monitoring_id');

        $unreadNonStatusChangeCount = (clone $baseUnreadNotificationsQuery)
            ->where('monitoring_notifications.type', '!=', NotificationType::STATUS_CHANGE->value)
            ->count();

        return $unreadStatusChangeCount + $unreadNonStatusChangeCount;
    }

    /**
     * @return Collection<string, int>
     */
    public function getUnreadNotificationCountsByUser(): Collection
    {
        $unreadStatusChangeCounts = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitoring_notifications.type', NotificationType::STATUS_CHANGE->value)
            ->where('monitoring_notifications.read', false)
            ->whereNull('monitorings.deleted_at')
            ->selectRaw('monitorings.user_id as user_id, count(distinct monitoring_notifications.monitoring_id) as total')
            ->groupBy('monitorings.user_id')
            ->pluck('total', 'user_id');

        $unreadNonStatusChangeCounts = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitoring_notifications.read', false)
            ->where('monitoring_notifications.type', '!=', NotificationType::STATUS_CHANGE->value)
            ->whereNull('monitorings.deleted_at')
            ->selectRaw('monitorings.user_id as user_id, count(*) as total')
            ->groupBy('monitorings.user_id')
            ->pluck('total', 'user_id');

        return $unreadStatusChangeCounts->keys()
            ->merge($unreadNonStatusChangeCounts->keys())
            ->unique()
            ->mapWithKeys(fn (string $userId): array => [
                $userId => (int) ($unreadStatusChangeCounts->get($userId, 0))
                    + (int) ($unreadNonStatusChangeCounts->get($userId, 0)),
            ]);
    }
}
