<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternalUi\MonitoringIndexRequest;
use App\Http\Resources\InternalUi\MonitoringResource;
use App\Models\User;
use App\Queries\MonitoringOverviewQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MonitoringIndexController extends Controller
{
    public function __invoke(MonitoringIndexRequest $request, MonitoringOverviewQuery $monitoringOverviewQuery): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $monitorings = $monitoringOverviewQuery->paginateFor(
            $user,
            $request->page(),
            $request->perPage(),
            $request->search(),
            $request->lifecycleStatus(),
        );

        return MonitoringResource::collection($monitorings)->additional([
            'meta' => [
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}
