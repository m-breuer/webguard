<?php

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Models\MonitoringNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $showRead = $request->boolean('show_read', false);

        $builder = MonitoringNotification::query()->where('type', NotificationType::STATUS_CHANGE);
        if (! $showRead) {
            $builder->where('read', false);
        }
        $statusChangeNotifications = $builder
            ->orderBy('read')->latest()
            ->limit(5)
            ->get();

        $sslExpiryNotificationsQuery = MonitoringNotification::query()->where('type', NotificationType::SSL_EXPIRY);
        if (! $showRead) {
            $sslExpiryNotificationsQuery->where('read', false);
        }
        $sslExpiryNotifications = $sslExpiryNotificationsQuery
            ->orderBy('read')->latest()
            ->limit(5)
            ->get();

        return view('notifications.index', compact('statusChangeNotifications', 'sslExpiryNotifications', 'showRead'));
    }

    public function markAsRead(MonitoringNotification $monitoringNotification): RedirectResponse
    {
        $monitoringNotification->read = true;
        $monitoringNotification->save();

        return back()->with('success', __('notifications.messages.notification_marked_as_read'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        MonitoringNotification::query()->where('read', false)->update(['read' => true]);

        return back()->with('success', __('notifications.messages.all_notifications_marked_as_read'));
    }

    public function loadMore(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $offset = $request->input('offset', 0);
        $limit = 5;
        $showRead = $request->boolean('show_read', false);

        $builder = MonitoringNotification::query()->where('type', NotificationType::from($type));
        if (! $showRead) {
            $builder->where('read', false);
        }
        $notifications = $builder
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
}
