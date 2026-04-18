<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Support\MonitoringStatusMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

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
        $latestStatusChangeTimestamps = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->selectRaw('monitoring_id, max(created_at) as latest_created_at')
            ->statusChange()
            ->when(! $showRead, fn (Builder $builder): Builder => $builder->unread())
            ->groupBy('monitoring_id');

        $monitorings = Monitoring::query()
            ->select([
                'monitorings.id',
                'monitorings.name',
                'monitorings.target',
                'monitorings.type',
                'monitorings.maintenance_from',
                'monitorings.maintenance_until',
            ])
            ->joinSub(
                MonitoringNotification::query()
                    ->withoutGlobalScopes()
                    ->from('monitoring_notifications as status_notifications')
                    ->select([
                        'status_notifications.id',
                        'status_notifications.monitoring_id',
                        'status_notifications.message',
                        'status_notifications.read',
                        'status_notifications.created_at',
                    ])
                    ->joinSub($latestStatusChangeTimestamps, 'latest_status_change_timestamps', function (JoinClause $joinClause): void {
                        $joinClause->on(
                            'latest_status_change_timestamps.monitoring_id',
                            '=',
                            'status_notifications.monitoring_id'
                        )->on(
                            'latest_status_change_timestamps.latest_created_at',
                            '=',
                            'status_notifications.created_at'
                        );
                    })
                    ->where('status_notifications.type', NotificationType::STATUS_CHANGE->value)
                    ->unless($showRead, fn (Builder $builder): Builder => $builder->where('status_notifications.read', false))
                    ->leftJoin('monitoring_notifications as newer_status_notifications', function (JoinClause $joinClause) use ($showRead): void {
                        $joinClause->on(
                            'newer_status_notifications.monitoring_id',
                            '=',
                            'status_notifications.monitoring_id'
                        )->on(
                            'newer_status_notifications.created_at',
                            '=',
                            'status_notifications.created_at'
                        )->whereColumn(
                            'newer_status_notifications.id',
                            '>',
                            'status_notifications.id'
                        )->where(
                            'newer_status_notifications.type',
                            NotificationType::STATUS_CHANGE->value
                        );

                        if (! $showRead) {
                            $joinClause->where('newer_status_notifications.read', false);
                        }
                    })
                    ->whereNull('newer_status_notifications.id'),
                'latest_status_notifications',
                fn (JoinClause $joinClause): JoinClause => $joinClause->on(
                    'latest_status_notifications.monitoring_id',
                    '=',
                    'monitorings.id'
                )
            )
            ->selectSub(
                MonitoringResponse::query()
                    ->select('http_status_code')
                    ->whereColumn('monitoring_response_results.monitoring_id', 'monitorings.id')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(1),
                'latest_status_code'
            )
            ->selectSub(
                MonitoringResponse::query()
                    ->select('created_at')
                    ->whereColumn('monitoring_response_results.monitoring_id', 'monitorings.id')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(1),
                'latest_checked_at'
            )
            ->selectRaw('latest_status_notifications.id as notification_id')
            ->selectRaw('latest_status_notifications.message as status_change_message')
            ->selectRaw('latest_status_notifications.read as notification_read')
            ->selectRaw('latest_status_notifications.created_at as latest_status_change_at')
            ->latest('latest_status_change_at')
            ->orderByDesc('notification_id')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        return $monitorings->map(function (Monitoring $monitoring): array {
            $latestStatusCode = $monitoring->getAttribute('latest_status_code');
            $maintenanceActive = $monitoring->isUnderMaintenance();

            $statusIdentifier = MonitoringStatusMeta::identifier($latestStatusCode !== null ? (int) $latestStatusCode : null, $maintenanceActive);
            $statusChangeMessage = (string) $monitoring->getAttribute('status_change_message');
            $latestCheckedAt = $monitoring->getAttribute('latest_checked_at');
            $latestStatusChangeAt = $monitoring->getAttribute('latest_status_change_at');
            $statusChangeIdentifier = $maintenanceActive
                ? 'maintenance'
                : MonitoringNotification::extractStatusChangeIdentifierFromMessage($statusChangeMessage);

            return [
                'notification_id' => (string) $monitoring->getAttribute('notification_id'),
                'monitoring_id' => $monitoring->id,
                'monitor_name' => $monitoring->name,
                'target' => $monitoring->target,
                'type' => $monitoring->type->value,
                'latest_status_code' => $latestStatusCode !== null ? (int) $latestStatusCode : null,
                'latest_checked_at' => $latestCheckedAt ? Date::parse((string) $latestCheckedAt)->toIso8601String() : null,
                'latest_status_change_at' => $latestStatusChangeAt ? Date::parse((string) $latestStatusChangeAt)->toIso8601String() : null,
                'status_identifier' => MonitoringStatusMeta::statusIdentifier(
                    $latestStatusCode !== null ? (int) $latestStatusCode : null,
                    $maintenanceActive
                ),
                'status_key' => MonitoringStatusMeta::statusKey(
                    $latestStatusCode !== null ? (int) $latestStatusCode : null,
                    $maintenanceActive
                ),
                'status_change_key' => 'notifications.status_change.' . $statusChangeIdentifier,
                'badge_type' => MonitoringStatusMeta::badgeType($statusIdentifier),
                'read' => (bool) $monitoring->getAttribute('notification_read'),
            ];
        });
    }

    public function getUnreadNotificationCount(): int
    {
        $builder = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitorings.user_id', auth()->id())
            ->whereNull('monitorings.deleted_at')
            ->where('monitoring_notifications.read', false);

        $unreadStatusChangeCount = (clone $builder)
            ->where('monitoring_notifications.type', NotificationType::STATUS_CHANGE->value)
            ->distinct('monitoring_notifications.monitoring_id')
            ->count('monitoring_notifications.monitoring_id');

        $unreadNonStatusChangeCount = (clone $builder)
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
