<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PublicStatusPayloadService;
use Illuminate\Http\JsonResponse;

class PublicStatusPayloadController extends Controller
{
    public function __invoke(string $status, PublicStatusPayloadService $publicStatusPayloadService): JsonResponse
    {
        return response()
            ->json(['data' => $publicStatusPayloadService->payload($status)])
            ->header('Cache-Control', 'public, max-age=30, stale-while-revalidate=60');
    }
}
