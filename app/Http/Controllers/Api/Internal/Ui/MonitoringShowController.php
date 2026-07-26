<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Resources\InternalUi\MonitoringResource;
use App\Models\User;
use App\Queries\MonitoringDetailQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringShowController extends Controller
{
    public function __invoke(Request $request, string $monitoring, MonitoringDetailQuery $monitoringDetailQuery): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        return MonitoringResource::make($monitoringDetailQuery->findVisible($user, $monitoring));
    }
}
