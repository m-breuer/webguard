<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PublicStatusSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStatusSubscriptionController extends Controller
{
    public function __invoke(string $status, Request $request, PublicStatusSubscriptionService $publicStatusSubscriptionService): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $publicStatusSubscriptionService->subscribe($status, $validated['email']);

        return response()->json([
            'data' => ['message' => 'Check your inbox to confirm your subscription.'],
        ], 202);
    }

    public function destroy(string $status, string $token, Request $request, PublicStatusSubscriptionService $publicStatusSubscriptionService): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        return response()->json([
            'data' => [
                'is_public' => $publicStatusSubscriptionService->unsubscribe($status, $validated['email'], $token),
            ],
        ]);
    }

    public function confirm(string $status, string $token, PublicStatusSubscriptionService $publicStatusSubscriptionService): JsonResponse
    {
        return response()->json([
            'data' => [
                'is_public' => $publicStatusSubscriptionService->confirm($status, $token),
            ],
        ]);
    }
}
