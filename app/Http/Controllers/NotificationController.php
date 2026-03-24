<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Models\MonitoringNotification;
use App\Models\Scopes\UserScope;
use App\Services\NotificationBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationBoardService $notificationBoardService): View
    {
        $showRead = $request->boolean('show_read', false);

        [$statusBoardEntries, $statusChangeHasMore] = $this->loadStatusBoardEntries(
            $notificationBoardService,
            $showRead
        );

        $sslExpiryNotificationsQuery = MonitoringNotification::query()->sslExpiry();
        if (! $showRead) {
            $sslExpiryNotificationsQuery->unread();
        }
        $sslExpiryNotifications = $sslExpiryNotificationsQuery
            ->with(['monitoring:id,name'])
            ->orderBy('read')->latest()
            ->limit(5)
            ->get();

        return view('notifications.index', compact('statusBoardEntries', 'statusChangeHasMore', 'sslExpiryNotifications', 'showRead'));
    }

    public function markAsRead(string $notificationId): RedirectResponse
    {
        $monitoringNotification = MonitoringNotification::query()->withoutGlobalScope(UserScope::class)->findOrFail($notificationId);

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
        $type = $request->input('type');
        $offset = (int) $request->input('offset', 0);
        $limit = 5;
        $showRead = $request->boolean('show_read', false);

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

        $builder = MonitoringNotification::query()->ofType(NotificationType::from($type));
        if (! $showRead) {
            $builder->unread();
        }
        $notifications = $builder
            ->with(['monitoring:id,name'])
            ->orderBy('read')->latest()
            ->offset($offset)
            ->limit($limit + 1) // Fetch one more to check if there are more
            ->get();

        $hasMore = $notifications->count() > $limit;
        if ($hasMore) {
            $notifications->pop(); // Remove the extra item
        }

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
        int $limit = 5
    ): array {
        $entries = $notificationBoardService->getStatusBoardEntries($showRead, $offset, $limit);
        $hasMore = $entries->count() > $limit;

        if ($hasMore) {
            $entries = $entries->slice(0, $limit)->values();
        }

        return [$entries, $hasMore];
    }
}
