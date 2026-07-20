<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Support\MonitoringStatusMeta;
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
        $statusChangeNotifications = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->select([
                'monitoring_notifications.id',
                'monitoring_notifications.monitoring_id',
                'monitoring_notifications.message',
                'monitoring_notification_states.read_at',
                'monitoring_notifications.created_at',
            ])
            ->join('monitoring_notification_states', 'monitoring_notification_states.monitoring_notification_id', '=', 'monitoring_notifications.id')
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitoring_notification_states.user_id', auth()->id())
            ->where(function ($query): void {
                $query->where('monitorings.user_id', auth()->id())
                    ->orWhereExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('team_memberships')
                            ->whereColumn('team_memberships.team_id', 'monitorings.team_id')
                            ->where('team_memberships.user_id', auth()->id());
                    });
            })
            ->whereNull('monitorings.deleted_at')
            ->where('monitoring_notifications.type', NotificationType::STATUS_CHANGE->value)
            ->unless($showRead, fn ($query) => $query->whereNull('monitoring_notification_states.read_at'))
            ->whereNotExists(function ($query) use ($showRead): void {
                $query->selectRaw('1')
                    ->from('monitoring_notifications as newer_notifications')
                    ->join('monitoring_notification_states as newer_states', 'newer_states.monitoring_notification_id', '=', 'newer_notifications.id')
                    ->whereColumn('newer_notifications.monitoring_id', 'monitoring_notifications.monitoring_id')
                    ->whereColumn('newer_states.user_id', 'monitoring_notification_states.user_id')
                    ->where('newer_notifications.type', NotificationType::STATUS_CHANGE->value)
                    ->when(! $showRead, fn ($query) => $query->whereNull('newer_states.read_at'))
                    ->where(function ($query): void {
                        $query->whereColumn('newer_notifications.created_at', '>', 'monitoring_notifications.created_at')
                            ->orWhere(function ($query): void {
                                $query->whereColumn('newer_notifications.created_at', 'monitoring_notifications.created_at')
                                    ->whereColumn('newer_notifications.id', '>', 'monitoring_notifications.id');
                            });
                    });
            })
            ->latest('monitoring_notifications.created_at')
            ->orderByDesc('monitoring_notifications.id')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $monitorings = Monitoring::query()
            ->select([
                'id',
                'name',
                'target',
                'type',
                'maintenance_from',
                'maintenance_until',
            ])
            ->withMaintenanceWindowState()
            ->whereKey($statusChangeNotifications->pluck('monitoring_id')->all())
            ->with([
                'latestResponseResult' => fn ($builder) => $builder->select([
                    'monitoring_response_results.id',
                    'monitoring_response_results.monitoring_id',
                    'monitoring_response_results.http_status_code',
                    'monitoring_response_results.created_at',
                ]),
            ])
            ->get()
            ->keyBy('id');

        return $statusChangeNotifications->map(function (MonitoringNotification $monitoringNotification) use ($monitorings): array {
            /** @var Monitoring $monitoring */
            $monitoring = $monitorings->get($monitoringNotification->monitoring_id);
            /** @var MonitoringResponse|null $latestResponse */
            $latestResponse = $monitoring->getRelation('latestResponseResult');
            $latestStatusCode = $latestResponse?->http_status_code;
            $maintenanceActive = $monitoring->isUnderMaintenance();

            $statusIdentifier = MonitoringStatusMeta::identifier($latestStatusCode !== null ? (int) $latestStatusCode : null, $maintenanceActive);
            $statusChangeMessage = $monitoringNotification->message;
            $latestCheckedAt = $latestResponse?->created_at;
            $latestStatusChangeAt = $monitoringNotification->created_at;
            $statusChangeIdentifier = $maintenanceActive
                ? 'maintenance'
                : MonitoringNotification::extractStatusChangeIdentifierFromMessage($statusChangeMessage);

            return [
                'notification_id' => $monitoringNotification->id,
                'monitoring_id' => $monitoring->id,
                'monitor_name' => $monitoring->name,
                'target' => $monitoring->target,
                'type' => $monitoring->type->value,
                'latest_status_code' => $latestStatusCode !== null ? (int) $latestStatusCode : null,
                'latest_checked_at' => $latestCheckedAt ? Date::parse($latestCheckedAt)->toIso8601String() : null,
                'latest_status_change_at' => $latestStatusChangeAt ? Date::parse($latestStatusChangeAt)->toIso8601String() : null,
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
                'read' => $monitoringNotification->read_at !== null,
            ];
        });
    }

    public function getUnreadNotificationCount(): int
    {
        $total = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitoring_notification_states', 'monitoring_notification_states.monitoring_notification_id', '=', 'monitoring_notifications.id')
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitoring_notification_states.user_id', auth()->id())
            ->where(function ($query): void {
                $query->where('monitorings.user_id', auth()->id())
                    ->orWhereExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('team_memberships')
                            ->whereColumn('team_memberships.team_id', 'monitorings.team_id')
                            ->where('team_memberships.user_id', auth()->id());
                    });
            })
            ->whereNull('monitorings.deleted_at')
            ->whereNull('monitoring_notification_states.read_at')
            ->selectRaw(
                <<<'SQL'
                count(distinct case
                    when monitoring_notifications.type = ?
                    then monitoring_notifications.monitoring_id
                end) + coalesce(sum(case
                    when monitoring_notifications.type != ?
                    then 1
                    else 0
                end), 0) as total
                SQL,
                [
                    NotificationType::STATUS_CHANGE->value,
                    NotificationType::STATUS_CHANGE->value,
                ]
            )
            ->value('total');

        return (int) $total;
    }

    /**
     * @return Collection<string, int>
     */
    public function getUnreadNotificationCountsByUser(): Collection
    {
        return MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitoring_notification_states', 'monitoring_notification_states.monitoring_notification_id', '=', 'monitoring_notifications.id')
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->whereNull('monitoring_notification_states.read_at')
            ->where(function ($query): void {
                $query->whereColumn('monitorings.user_id', 'monitoring_notification_states.user_id')
                    ->orWhereExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('team_memberships')
                            ->whereColumn('team_memberships.team_id', 'monitorings.team_id')
                            ->whereColumn('team_memberships.user_id', 'monitoring_notification_states.user_id');
                    });
            })
            ->whereNull('monitorings.deleted_at')
            ->selectRaw(
                <<<'SQL'
                monitoring_notification_states.user_id as user_id,
                count(distinct case
                    when monitoring_notifications.type = ?
                    then monitoring_notifications.monitoring_id
                end) + coalesce(sum(case
                    when monitoring_notifications.type != ?
                    then 1
                    else 0
                end), 0) as total
                SQL,
                [
                    NotificationType::STATUS_CHANGE->value,
                    NotificationType::STATUS_CHANGE->value,
                ]
            )
            ->groupBy('monitoring_notification_states.user_id')
            ->pluck('total', 'user_id')
            ->map(fn (int|string $total): int => (int) $total);
    }
}
