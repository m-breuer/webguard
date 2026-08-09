<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\MobileMonitoringDetailRequest;
use App\Models\User;
use App\Queries\MonitoringDetailQuery;
use App\Services\MobileMonitoringDetailPayloadService;
use Illuminate\Http\JsonResponse;

/**
 * @group Mobile monitoring detail
 *
 * Read the bounded, server-derived diagnostic detail for one visible monitoring.
 */
final class MobileMonitoringDetailController extends Controller
{
    public function __invoke(
        MobileMonitoringDetailRequest $mobileMonitoringDetailRequest,
        string $monitoring,
        MonitoringDetailQuery $monitoringDetailQuery,
        MobileMonitoringDetailPayloadService $mobileMonitoringDetailPayloadService,
    ): JsonResponse {
        /** @var User $user */
        $user = $mobileMonitoringDetailRequest->user();

        return response()->json($mobileMonitoringDetailPayloadService->for(
            $monitoringDetailQuery->findVisible($user, $monitoring),
            $user,
            $mobileMonitoringDetailRequest->days(),
            $mobileMonitoringDetailRequest->incidentLimit(),
            $mobileMonitoringDetailRequest->incidentOffset(),
        ));
    }
}
