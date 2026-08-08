<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServerHealthReportRequest;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BearerServerHealthReportController extends Controller
{
    public function __invoke(
        ServerHealthReportRequest $serverHealthReportRequest,
        Monitoring $monitoring,
        ServerHealthReportController $serverHealthReportController
    ): JsonResponse {
        /** @var User $user */
        $user = $serverHealthReportRequest->user();

        abort_unless($monitoring->isServerHealth() && $monitoring->isManageableBy($user), 404);

        return $serverHealthReportController->store($serverHealthReportRequest, $monitoring);
    }
}
