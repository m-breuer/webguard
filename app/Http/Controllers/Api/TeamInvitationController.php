<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\External\TeamInvitationResource;
use App\Http\Resources\External\TeamResource;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Teams\TeamInvitationService;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Teams
 *
 * Manage and accept team invitations.
 */
class TeamInvitationController extends Controller
{
    public function index(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $teamMembershipService->assertAdmin($team, $user);

        return response()->json([
            'data' => $team->invitations()->whereNull('accepted_at')->latest()->orderByDesc('id')->get()->map(
                fn (TeamInvitation $teamInvitation): array => TeamInvitationResource::make($teamInvitation)->resolve($request)
            )->values(),
        ]);
    }

    public function store(
        Request $request,
        Team $team,
        TeamMembershipService $teamMembershipService,
        TeamInvitationService $teamInvitationService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'role' => ['required', Rule::enum(TeamRole::class)],
        ]);

        $teamInvitation = $teamInvitationService->invite(
            $team,
            $user,
            (string) $validated['email'],
            TeamRole::from((string) $validated['role'])
        );

        return response()->json(['data' => TeamInvitationResource::make($teamInvitation)->resolve($request)], 201);
    }

    public function destroy(
        Request $request,
        Team $team,
        TeamInvitation $teamInvitation,
        TeamMembershipService $teamMembershipService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamInvitation->team_id === $team->id, 404);

        $teamInvitation->delete();

        return response()->json(status: 204);
    }

    public function accept(Request $request, string $token, TeamInvitationService $teamInvitationService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $team = $teamInvitationService->accept($token, $user);

        return response()->json(['data' => TeamResource::make($team)->resolve($request)]);
    }
}
