<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationState;
use App\Models\NotificationChannelDelivery;
use App\Services\NotificationBoardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private const int DEFAULT_NOTIFICATION_LIMIT = 5;

    private const int MAX_NOTIFICATION_LIMIT = 100;

    public function index(Request $request): View
    {
        $showRead = $request->boolean('show_read', false);
        $limit = $this->resolveRequestedLimit($request);

        return view('notifications.index', compact(
            'showRead',
            'limit'
        ));
    }

    public function markAsRead(string $notificationId): RedirectResponse
    {
        $monitoringNotification = MonitoringNotification::query()->findOrFail($notificationId);

        if ($monitoringNotification->type === NotificationType::STATUS_CHANGE) {
            $notificationIds = MonitoringNotification::query()
                ->where('monitoring_id', $monitoringNotification->monitoring_id)
                ->statusChange()
                ->where(function (Builder $builder) use ($monitoringNotification): void {
                    $builder->where('created_at', '<', $monitoringNotification->created_at)
                        ->orWhere(function (Builder $builder) use ($monitoringNotification): void {
                            $builder->where('created_at', $monitoringNotification->created_at)
                                ->where('id', '<=', $monitoringNotification->id);
                        });
                })
                ->pluck('id');

            MonitoringNotificationState::query()
                ->where('user_id', auth()->id())
                ->whereIn('monitoring_notification_id', $notificationIds)
                ->update(['read_at' => now()]);

            MonitoringNotification::query()
                ->whereIn('id', $notificationIds)
                ->update(['read' => true]);
        } else {
            MonitoringNotificationState::query()
                ->firstOrCreate([
                    'monitoring_notification_id' => $monitoringNotification->id,
                    'user_id' => auth()->id(),
                ])
                ->update(['read_at' => now()]);
            $monitoringNotification->update(['read' => true]);
        }

        return back()->with('success', __('notifications.messages.notification_marked_as_read'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        $notificationIds = MonitoringNotificationState::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->pluck('monitoring_notification_id');

        MonitoringNotificationState::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $notificationIds)
            ->update(['read' => true]);

        return back()->with('success', __('notifications.messages.all_notifications_marked_as_read'));
    }

    public function loadMore(Request $request, NotificationBoardService $notificationBoardService): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in($this->loadMoreTypes())],
            'offset' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_NOTIFICATION_LIMIT],
            'show_read' => ['nullable', 'boolean'],
        ]);

        $type = (string) $validated['type'];
        $offset = (int) ($validated['offset'] ?? 0);
        $limit = (int) ($validated['limit'] ?? self::DEFAULT_NOTIFICATION_LIMIT);
        $showRead = (bool) ($validated['show_read'] ?? false);

        if ($type === NotificationType::STATUS_CHANGE->value) {
            [$statusBoardEntries, $hasMore] = $this->loadStatusBoardEntries(
                $notificationBoardService,
                $showRead,
                $offset,
                $limit
            );

            $renderedHtml = view('notifications.partials.status_board_list', ['entries' => $statusBoardEntries])->render();

            return response()->json([
                'html' => $renderedHtml,
                'hasMore' => $hasMore,
                'count' => $statusBoardEntries->count(),
            ]);
        }

        if ($type === 'delivery_history') {
            [$deliveryHistory, $hasMore] = $this->loadDeliveryHistory($offset, $limit);

            $renderedHtml = view('notifications.partials.delivery_history_list', ['deliveries' => $deliveryHistory])->render();

            return response()->json([
                'html' => $renderedHtml,
                'hasMore' => $hasMore,
                'count' => $deliveryHistory->count(),
            ]);
        }

        [$notifications, $hasMore] = $this->loadNotificationsByType(
            NotificationType::from($type),
            $showRead,
            $offset,
            $limit
        );

        $renderedHtml = view('notifications.partials.notification_list', compact('notifications', 'type'))->render();

        return response()->json([
            'html' => $renderedHtml,
            'hasMore' => $hasMore,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: bool}
     */
    private function loadStatusBoardEntries(
        NotificationBoardService $notificationBoardService,
        bool $showRead,
        int $offset = 0,
        int $limit = self::DEFAULT_NOTIFICATION_LIMIT
    ): array {
        $entries = $notificationBoardService->getStatusBoardEntries($showRead, $offset, $limit);
        $hasMore = $entries->count() > $limit;

        if ($hasMore) {
            $entries = $entries->slice(0, $limit)->values();
        }

        return [$entries, $hasMore];
    }

    /**
     * @return array{0: Collection<int, MonitoringNotification>, 1: bool}
     */
    private function loadSslExpiryNotifications(
        bool $showRead,
        int $offset = 0,
        int $limit = self::DEFAULT_NOTIFICATION_LIMIT
    ): array {
        return $this->loadNotificationsByType(NotificationType::SSL_EXPIRY, $showRead, $offset, $limit);
    }

    /**
     * @return array{0: Collection<int, MonitoringNotification>, 1: bool}
     */
    private function loadDomainExpiryNotifications(
        bool $showRead,
        int $offset = 0,
        int $limit = self::DEFAULT_NOTIFICATION_LIMIT
    ): array {
        return $this->loadNotificationsByType(NotificationType::DOMAIN_EXPIRY, $showRead, $offset, $limit);
    }

    /**
     * @return array{0: Collection<int, NotificationChannelDelivery>, 1: bool}
     */
    private function loadDeliveryHistory(int $offset = 0, int $limit = self::DEFAULT_NOTIFICATION_LIMIT): array
    {
        $deliveries = NotificationChannelDelivery::query()
            ->where('user_id', auth()->id())
            ->with(['monitoringNotification.monitoring:id,name,target'])
            ->latest('created_at')
            ->latest('id')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $hasMore = $deliveries->count() > $limit;
        if ($hasMore) {
            $deliveries->pop();
        }

        return [$deliveries, $hasMore];
    }

    /**
     * @return array{0: Collection<int, MonitoringNotification>, 1: bool}
     */
    private function loadNotificationsByType(
        NotificationType $notificationType,
        bool $showRead,
        int $offset,
        int $limit
    ): array {
        $builder = MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->select('monitoring_notifications.*', 'monitoring_notification_states.read_at as user_read_at')
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
            ->ofType($notificationType);
        if (! $showRead) {
            $builder->whereNull('monitoring_notification_states.read_at');
        }

        $notifications = $builder
            ->with(['monitoring:id,name'])
            ->orderByRaw('monitoring_notification_states.read_at is not null')
            ->latest('monitoring_notifications.created_at')
            ->latest('monitoring_notifications.id')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $notifications->each(function (MonitoringNotification $monitoringNotification): void {
            $monitoringNotification->setAttribute('read', $monitoringNotification->user_read_at !== null);
        });

        $hasMore = $notifications->count() > $limit;
        if ($hasMore) {
            $notifications->pop();
        }

        return [$notifications, $hasMore];
    }

    private function resolveRequestedLimit(Request $request): int
    {
        $limit = $request->query('limit');
        if (! is_scalar($limit) || ! is_numeric($limit)) {
            return self::DEFAULT_NOTIFICATION_LIMIT;
        }

        $parsedLimit = (int) $limit;
        if ($parsedLimit < 1) {
            return self::DEFAULT_NOTIFICATION_LIMIT;
        }

        return min($parsedLimit, self::MAX_NOTIFICATION_LIMIT);
    }

    /**
     * @return list<string>
     */
    private function loadMoreTypes(): array
    {
        return [
            ...NotificationType::values(),
            'delivery_history',
        ];
    }
}
