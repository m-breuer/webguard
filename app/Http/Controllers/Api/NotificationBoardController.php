<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationBoardController extends Controller
{
    public function __invoke(Request $request, NotificationBoardService $notificationBoardService): JsonResponse
    {
        $validated = $request->validate([
            'offset' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'show_read' => ['nullable', 'boolean'],
        ]);

        $offset = (int) ($validated['offset'] ?? 0);
        $limit = (int) ($validated['limit'] ?? 10);
        $showRead = (bool) ($validated['show_read'] ?? false);

        $entries = $notificationBoardService->getStatusBoardEntries($showRead, $offset, $limit);
        $hasMore = $entries->count() > $limit;

        if ($hasMore) {
            $entries = $entries->slice(0, $limit)->values();
        }

        return response()->json([
            'data' => $entries,
            'meta' => [
                'offset' => $offset,
                'limit' => $limit,
                'has_more' => $hasMore,
                'count' => $entries->count(),
            ],
        ]);
    }
}

