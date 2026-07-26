<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OperationsOverviewPayloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperationsOverviewPayloadService $operationsOverviewPayloadService): Response
    {
        $validated = $request->validate([
            'service_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $payload = $operationsOverviewPayloadService->for($user, (int) ($validated['service_page'] ?? 1));
        $etag = '"' . hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)) . '"';

        if (in_array($etag, $request->getETags(), true)) {
            return response('', Response::HTTP_NOT_MODIFIED)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, max-age=0, must-revalidate');
        }

        return response()->json([
            'data' => $payload['data'],
            'meta' => [
                'as_of' => now()->toIso8601String(),
                'service_pagination' => $payload['service_pagination'],
            ],
        ])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }
}
