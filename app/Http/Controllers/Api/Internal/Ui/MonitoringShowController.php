<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Resources\InternalUi\MonitoringResource;
use App\Models\User;
use App\Queries\MonitoringDetailQuery;
use App\Services\MonitoringCheckIntervalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringShowController extends Controller
{
    public function __invoke(
        Request $request,
        string $monitoring,
        MonitoringDetailQuery $monitoringDetailQuery,
        MonitoringCheckIntervalService $monitoringCheckIntervalService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $monitoring = $monitoringDetailQuery->findVisible($user, $monitoring);
        $payload = MonitoringResource::make($monitoring)->resolve($request);
        $payload['initial_results_wait_minutes'] = $monitoring->isActive()
            && $monitoring->latestResponseResult === null
            && ! $monitoring->isHeartbeat()
            && ! $monitoring->isServerHealth()
            ? (int) ceil($monitoringCheckIntervalService->secondsFor($monitoring) / 60)
            : null;

        return response()->json(['data' => $payload]);
    }
}
