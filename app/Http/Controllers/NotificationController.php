<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Models\MonitoringNotification;
use App\Services\NotificationBoardService;
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

    public function index(Request $request, NotificationBoardService $notificationBoardService): View
    {
        $showRead = $request->boolean('show_read', false);
        $limit = $this->resolveRequestedLimit($request);

        [$statusBoardEntries, $statusChangeHasMore] = $this->loadStatusBoardEntries(
            $notificationBoardService,
            $showRead,
            0,
            $limit
        );

        [$sslExpiryNotifications, $sslExpiryHasMore] = $this->loadSslExpiryNotifications($showRead, 0, $limit);

        return view('notifications.index', compact(
            'statusBoardEntries',
            'statusChangeHasMore',
            'sslExpiryNotifications',
            'sslExpiryHasMore',
            'showRead',
            'limit'
        ));
    }

    public function markAsRead(string $notificationId): RedirectResponse
    {
        $monitoringNotification = MonitoringNotification::query()->findOrFail($notificationId);

        $monitoringNotification->read = true;
        $monitoringNotification->save();

        return back()->with('success', __('notifications.messages.notification_marked_as_read'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        MonitoringNotification::query()->unread()->update(['read' => true]);

        return back()->with('success', __('notifications.messages.all_notifications_marked_as_read'));
    }

    public function loadMore(Request $request, NotificationBoardService $notificationBoardService): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(NotificationType::values())],
            'offset' => ['nullable', 'integer', 'min:0'],
            'show_read' => ['nullable', 'boolean'],
        ]);

        $type = (string) $validated['type'];
        $offset = (int) ($validated['offset'] ?? 0);
        $limit = self::DEFAULT_NOTIFICATION_LIMIT;
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
    private function loadNotificationsByType(
        NotificationType $notificationType,
        bool $showRead,
        int $offset,
        int $limit
    ): array {
        $builder = MonitoringNotification::query()->ofType($notificationType);
        if (! $showRead) {
            $builder->unread();
        }

        $notifications = $builder
            ->with(['monitoring:id,name'])
            ->orderBy('read')->latest()
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

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
}
