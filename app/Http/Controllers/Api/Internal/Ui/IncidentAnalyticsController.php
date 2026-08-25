<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncidentAnalyticsRequest;
use App\Models\User;
use App\Services\IncidentAnalyticsPayloadService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class IncidentAnalyticsController extends Controller
{
    public function __invoke(IncidentAnalyticsRequest $incidentAnalyticsRequest, IncidentAnalyticsPayloadService $incidentAnalyticsPayloadService): JsonResponse|Response
    {
        /** @var User $user */
        $user = $incidentAnalyticsRequest->user();
        $payload = $incidentAnalyticsPayloadService->for(
            $user,
            $incidentAnalyticsRequest->validated(),
            $incidentAnalyticsRequest->integer('page', 1),
        );
        $etag = '"' . hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)) . '"';

        if (in_array($etag, $incidentAnalyticsRequest->getETags(), true)) {
            return response('', Response::HTTP_NOT_MODIFIED)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, max-age=0, must-revalidate');
        }

        return response()->json([
            'data' => $payload['data'],
            'meta' => [
                'as_of' => now()->toIso8601String(),
                'incident_pagination' => $payload['pagination'],
            ],
        ])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }
}
