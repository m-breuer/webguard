<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
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
            'data' => $team->memberships()->with('user:id,name,email')->orderBy('created_at')->get(),
        ]);
    }

    public function update(
        Request $request,
        Team $team,
        TeamMembership $membership,
        TeamMembershipService $teamMembershipService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($membership->team_id === $team->id, 404);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(TeamRole::class)],
        ]);

        $teamMembershipService->changeRole($membership, TeamRole::from((string) $validated['role']));

        return response()->json(['data' => $membership->refresh()->load('user:id,name,email')]);
    }

    public function destroy(
        Request $request,
        Team $team,
        TeamMembership $membership,
        TeamMembershipService $teamMembershipService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($membership->team_id === $team->id, 404);

        $teamMembershipService->remove($membership);

        return response()->json(status: 204);
    }
}
