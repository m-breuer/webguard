<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MonitoringCardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MonitoringCardDataController extends Controller
{
    public function __invoke(
        Request $request,
        MonitoringCardDataService $monitoringCardDataService,
    ): JsonResponse {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'ids' => ['nullable', 'array', 'max:100'],
            'ids.*' => ['required', 'string'],
            'summary_ids' => ['nullable', 'array', 'max:100'],
            'summary_ids.*' => ['required', 'string'],
        ]);

        /** @var Collection<int, string> $requestedIds */
        $requestedIds = collect($validated['ids'] ?? [])
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();
        /** @var Collection<int, string> $summaryIds */
        $summaryIds = collect($validated['summary_ids'] ?? $requestedIds->all())
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();

        abort_if($requestedIds->isEmpty() && $summaryIds->isEmpty(), 422, 'At least one monitoring id is required.');

        return response()->json($monitoringCardDataService->for(
            $request->user(),
            $requestedIds,
            $summaryIds,
            array_key_exists('summary_ids', $validated),
        ));
    }
}
