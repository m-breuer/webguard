<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\External\TeamMembershipResource;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Teams
 *
 * Manage team members and roles.
 */
class TeamMemberController extends Controller
{
    public function index(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $teamMembershipService->assertMember($team, $user);

        return response()->json([
            'data' => $team->memberships()->with('user:id,name,email')->oldest()->orderBy('id')->get()->map(
                fn (TeamMembership $teamMembership): array => TeamMembershipResource::make($teamMembership)->resolve($request)
            )->values(),
        ]);
    }

    public function update(
        Request $request,
        Team $team,
        TeamMembership $teamMembership,
        TeamMembershipService $teamMembershipService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamMembership->team_id === $team->id, 404);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(TeamRole::class)],
        ]);

        $teamMembershipService->changeRole($teamMembership, TeamRole::from((string) $validated['role']));

        return response()->json([
            'data' => TeamMembershipResource::make($teamMembership->refresh()->load('user:id,name,email'))->resolve($request),
        ]);
    }

    public function destroy(
        Request $request,
        Team $team,
        TeamMembership $teamMembership,
        TeamMembershipService $teamMembershipService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamMembership->team_id === $team->id, 404);

        $teamMembershipService->remove($teamMembership);

        return response()->json(status: 204);
    }
}
