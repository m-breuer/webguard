<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OperationsOverviewPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperationsOverviewPayloadService $operationsOverviewPayloadService): JsonResponse
    {
        $validated = $request->validate([
            'service_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $payload = $operationsOverviewPayloadService->for($user, (int) ($validated['service_page'] ?? 1));

        return response()->json([
            'data' => $payload['data'],
            'meta' => [
                'as_of' => now()->toIso8601String(),
                'service_pagination' => $payload['service_pagination'],
            ],
        ]);
    }
}
