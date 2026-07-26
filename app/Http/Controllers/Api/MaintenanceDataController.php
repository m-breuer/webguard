<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MaintenanceOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceDataController extends Controller
{
    public function __invoke(Request $request, MaintenanceOverviewService $maintenanceOverviewService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('maintenance_status')->toString();
        $groupId = $request->string('monitoring_group_id')->toString();
        $sort = $request->string('sort')->toString() ?: 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        return response()->json([
            'data' => $maintenanceOverviewService->for(
                $user,
                $search,
                $status,
                $groupId,
                $sort,
                $direction,
                $request->integer('per_page', 50),
            ),
        ]);
    }
}
