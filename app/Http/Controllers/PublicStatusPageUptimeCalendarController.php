<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Services\StatusPageUptimeCalendarService;
use Illuminate\Http\JsonResponse;

final class PublicStatusPageUptimeCalendarController extends Controller
{
    public function __invoke(
        StatusPage $statusPage,
        StatusPageUptimeCalendarService $statusPageUptimeCalendarService
    ): JsonResponse {
        abort_unless($statusPage->is_public, 404);

        return response()->json(
            $statusPageUptimeCalendarService->getLast30Days($statusPage)->toArray()
        );
    }
}
