<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\Teams\TeamInvitationService;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TeamWorkspaceController extends Controller
{
    public function show(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        $teamMembershipService->assertMember($team, $user);

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function update(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        $team->update($request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000']]));

        return response()->json(['data' => $this->payload($team->refresh(), $user)]);
    }

    public function updateMember(Request $request, Team $team, TeamMembership $teamMembership, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamMembership->team_id === $team->id, 404);
        $data = $request->validate(['role' => ['required', Rule::enum(TeamRole::class)]]);
        $teamMembershipService->changeRole($teamMembership, TeamRole::from($data['role']));

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function destroyMember(Request $request, Team $team, TeamMembership $teamMembership, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamMembership->team_id === $team->id, 404);
        $teamMembershipService->remove($teamMembership);

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function invite(Request $request, Team $team, TeamMembershipService $teamMembershipService, TeamInvitationService $teamInvitationService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        $data = $request->validate(['email' => ['required', 'string', 'lowercase', 'email', 'max:255'], 'role' => ['required', Rule::enum(TeamRole::class)]]);
        $teamInvitationService->invite($team, $user, $data['email'], TeamRole::from($data['role']));

        return response()->json(['data' => $this->payload($team, $user)], 201);
    }

    public function destroyInvitation(Request $request, Team $team, TeamInvitation $teamInvitation, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamInvitation->team_id === $team->id, 404);
        $teamInvitation->delete();

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function leave(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        $teamMembershipService->assertMember($team, $user);
        $teamMembershipService->leave($team, $user);

        return response()->json(['data' => ['left' => true]]);
    }

    /** @return array<string, mixed> */
    private function payload(Team $team, User $user): array
    {
        $team->load(['memberships.user:id,name,email', 'invitations' => fn ($query) => $query->whereNull('accepted_at')->latest()])->loadCount('monitorings');

        return ['id' => $team->id, 'name' => $team->name, 'description' => $team->description, 'monitoring_count' => $team->monitorings_count, 'can_manage' => $team->isAdmin($user), 'members' => $team->memberships->map(fn (TeamMembership $teamMembership) => ['id' => $teamMembership->id, 'name' => $teamMembership->user->name, 'email' => $teamMembership->user->email, 'role' => $teamMembership->role->value])->values(), 'invitations' => $team->isAdmin($user) ? $team->invitations->map(fn (TeamInvitation $teamInvitation) => ['id' => $teamInvitation->id, 'email' => $teamInvitation->email, 'role' => $teamInvitation->role->value, 'expires_at' => $teamInvitation->expires_at?->toIso8601String()])->values() : []];
    }
}
