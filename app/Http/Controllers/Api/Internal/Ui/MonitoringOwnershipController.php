<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternalUiMonitoringOwnershipRequest;
use App\Models\Monitoring;
use App\Models\Team;
use App\Models\User;
use App\Services\Monitorings\MonitoringOwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MonitoringOwnershipController extends Controller
{
    public function moveToTeam(
        InternalUiMonitoringOwnershipRequest $request,
        string $monitoring,
        MonitoringOwnershipService $monitoringOwnershipService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $monitoring = $this->manageableMonitoring($user, $monitoring);
        $team = Team::query()->findOrFail($request->validated('team_id'));

        return response()->json([
            'data' => $this->payload($monitoringOwnershipService->moveToTeam($monitoring, $team, $user)),
        ]);
    }

    public function moveToPrivate(
        Request $request,
        string $monitoring,
        MonitoringOwnershipService $monitoringOwnershipService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $monitoring = $this->manageableMonitoring($user, $monitoring);

        return response()->json([
            'data' => $this->payload($monitoringOwnershipService->moveToPrivate($monitoring, $user)),
        ]);
    }

    private function manageableMonitoring(User $user, string $monitoring): Monitoring
    {
        $monitoring = Monitoring::query()->visibleTo($user)->whereKey($monitoring)->firstOrFail();
        abort_unless($monitoring->isManageableBy($user), 403);

        return $monitoring;
    }

    /**
     * @return array{id: string, ownership: array{type: string, team_id: string|null, team_name: string|null}}
     */
    private function payload(Monitoring $monitoring): array
    {
        $monitoring->loadMissing('team:id,name');

        return [
            'id' => $monitoring->id,
            'ownership' => [
                'type' => $monitoring->isTeamOwned() ? 'team' : 'private',
                'team_id' => $monitoring->team_id,
                'team_name' => $monitoring->team?->name,
            ],
        ];
    }
}
