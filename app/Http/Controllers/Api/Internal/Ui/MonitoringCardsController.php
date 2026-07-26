<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternalUi\MonitoringCardsRequest;
use App\Models\User;
use App\Services\MonitoringCardDataService;
use Illuminate\Http\JsonResponse;

class MonitoringCardsController extends Controller
{
    public function __invoke(MonitoringCardsRequest $request, MonitoringCardDataService $monitoringCardDataService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ids = collect($request->monitoringIds());

        return response()->json($monitoringCardDataService->for($user, $ids, $ids, true));
    }
}
