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
    public function __invoke(MonitoringIndexRequest $monitoringIndexRequest, MonitoringOverviewQuery $monitoringOverviewQuery): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $monitoringIndexRequest->user();
        $lengthAwarePaginator = $monitoringOverviewQuery->paginateFor(
            $user,
            $monitoringIndexRequest->page(),
            $monitoringIndexRequest->perPage(),
            $monitoringIndexRequest->search(),
            $monitoringIndexRequest->lifecycleStatus(),
        );

        return MonitoringResource::collection($lengthAwarePaginator)->additional([
            'meta' => [
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}
