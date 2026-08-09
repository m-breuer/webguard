<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MonitoringNotification;
use App\Models\User;
use App\Services\NotificationBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileNotificationBoardController extends Controller
{
    public function index(Request $request, NotificationBoardService $notificationBoardService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'event_type' => ['nullable', Rule::in([
                'incident', 'recovery', 'maintenance', 'performance_degraded', 'performance_recovered',
                'ssl_expiring', 'ssl_expired', 'domain_expiring', 'domain_expired', 'delivery_failure',
            ])],
            'show_read' => ['nullable', 'boolean'],
        ]);
        $entries = $notificationBoardService->mobileEntries(
            $user,
            $validated['cursor'] ?? null,
            (int) ($validated['limit'] ?? 25),
            $validated['event_type'] ?? null,
            (bool) ($validated['show_read'] ?? false),
        );
        $limit = (int) ($validated['limit'] ?? 25);
        $hasMore = $entries->count() > $limit;
        $entries = $entries->take($limit)->values();

        return response()->json([
            'data' => $entries,
            'meta' => [
                'next_cursor' => $hasMore ? $entries->last()['cursor'] : null,
                'has_more' => $hasMore,
                'unread_count' => $notificationBoardService->getUnreadNotificationCount(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notification, NotificationBoardService $notificationBoardService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notificationBoardService->markRead(
            $user,
            MonitoringNotification::query()->withoutGlobalScopes()->with('monitoring')->findOrFail($notification),
        );

        return response()->json(['data' => ['id' => $notification, 'read' => true]]);
    }

    public function markAllRead(Request $request, NotificationBoardService $notificationBoardService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notificationBoardService->markAllRead($user);

        return response()->json(['data' => ['read' => true], 'meta' => ['unread_count' => 0]]);
    }
}
