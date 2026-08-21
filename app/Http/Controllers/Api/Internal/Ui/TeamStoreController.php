<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\TeamRequest;
use App\Models\User;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\JsonResponse;

final class TeamStoreController extends Controller
{
    public function __invoke(TeamRequest $teamRequest, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $teamRequest->user();
        $team = $teamMembershipService->createTeam($user, $teamRequest->validated());

        return response()->json(['data' => ['id' => $team->id, 'name' => $team->name, 'role' => 'admin']], 201);
    }
}
