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
        $notificationRelation = $showRead
            ? 'latestStatusChangeNotification'
            : 'latestUnreadStatusChangeNotification';

        $monitorings = Monitoring::query()
            ->select([
                'id',
                'name',
                'target',
                'type',
                'maintenance_from',
                'maintenance_until',
            ])
            ->where('user_id', auth()->id())
            ->with([
                $notificationRelation => fn ($builder) => $builder->select([
                    'monitoring_notifications.id',
                    'monitoring_notifications.monitoring_id',
                    'monitoring_notifications.message',
                    'monitoring_notifications.read',
                    'monitoring_notifications.created_at',
                ]),
                'latestResponseResult' => fn ($builder) => $builder->select([
                    'monitoring_response_results.id',
                    'monitoring_response_results.monitoring_id',
                    'monitoring_response_results.http_status_code',
                    'monitoring_response_results.created_at',
                ]),
            ])
            ->get()
            ->filter(fn (Monitoring $monitoring): bool => $monitoring->getRelation($notificationRelation) !== null)
            ->sort(function (Monitoring $left, Monitoring $right) use ($notificationRelation): int {
                /** @var MonitoringNotification $leftNotification */
                $leftNotification = $left->getRelation($notificationRelation);
                /** @var MonitoringNotification $rightNotification */
                $rightNotification = $right->getRelation($notificationRelation);

                return [
                    $rightNotification->created_at?->getTimestamp() ?? 0,
                    $rightNotification->id,
                ] <=> [
                    $leftNotification->created_at?->getTimestamp() ?? 0,
                    $leftNotification->id,
                ];
            })
            ->slice($offset, $limit + 1)
            ->values();

        return $monitorings->map(function (Monitoring $monitoring) use ($notificationRelation): array {
            /** @var MonitoringNotification $latestStatusNotification */
            $latestStatusNotification = $monitoring->getRelation($notificationRelation);
            /** @var MonitoringResponse|null $latestResponse */
            $latestResponse = $monitoring->getRelation('latestResponseResult');
            $latestStatusCode = $latestResponse?->http_status_code;
            $maintenanceActive = $monitoring->isUnderMaintenance();

            $statusIdentifier = MonitoringStatusMeta::identifier($latestStatusCode !== null ? (int) $latestStatusCode : null, $maintenanceActive);
            $statusChangeMessage = $latestStatusNotification->message;
            $latestCheckedAt = $latestResponse?->created_at;
            $latestStatusChangeAt = $latestStatusNotification->created_at;
            $statusChangeIdentifier = $maintenanceActive
                ? 'maintenance'
                : MonitoringNotification::extractStatusChangeIdentifierFromMessage($statusChangeMessage);

            return [
                'notification_id' => $latestStatusNotification->id,
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
                'read' => $latestStatusNotification->read,
            ];
        });
    }

    public function getUnreadNotificationCount(): int
    {
        $total = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitorings.user_id', auth()->id())
            ->whereNull('monitorings.deleted_at')
            ->where('monitoring_notifications.read', false)
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
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->where('monitoring_notifications.read', false)
            ->whereNull('monitorings.deleted_at')
            ->selectRaw(
                <<<'SQL'
                monitorings.user_id as user_id,
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
            ->groupBy('monitorings.user_id')
            ->pluck('total', 'user_id')
            ->map(fn (int|string $total): int => (int) $total);
    }
}
