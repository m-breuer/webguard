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
    public function show(Request $request, Team $team, TeamMembershipService $memberships): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        $memberships->assertMember($team, $user);

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function update(Request $request, Team $team, TeamMembershipService $memberships): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $memberships->assertAdmin($team, $user);
        $team->update($request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000']]));

        return response()->json(['data' => $this->payload($team->refresh(), $user)]);
    }

    public function updateMember(Request $request, Team $team, TeamMembership $teamMembership, TeamMembershipService $memberships): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $memberships->assertAdmin($team, $user);
        abort_unless($teamMembership->team_id === $team->id, 404);
        $data = $request->validate(['role' => ['required', Rule::enum(TeamRole::class)]]);
        $memberships->changeRole($teamMembership, TeamRole::from($data['role']));

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function destroyMember(Request $request, Team $team, TeamMembership $teamMembership, TeamMembershipService $memberships): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $memberships->assertAdmin($team, $user);
        abort_unless($teamMembership->team_id === $team->id, 404);
        $memberships->remove($teamMembership);

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function invite(Request $request, Team $team, TeamMembershipService $memberships, TeamInvitationService $invitations): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $memberships->assertAdmin($team, $user);
        $data = $request->validate(['email' => ['required', 'string', 'lowercase', 'email', 'max:255'], 'role' => ['required', Rule::enum(TeamRole::class)]]);
        $invitations->invite($team, $user, $data['email'], TeamRole::from($data['role']));

        return response()->json(['data' => $this->payload($team, $user)], 201);
    }

    public function destroyInvitation(Request $request, Team $team, TeamInvitation $teamInvitation, TeamMembershipService $memberships): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        abort_if($user->isDemo(), 403);
        $memberships->assertAdmin($team, $user);
        abort_unless($teamInvitation->team_id === $team->id, 404);
        $teamInvitation->delete();

        return response()->json(['data' => $this->payload($team, $user)]);
    }

    public function leave(Request $request, Team $team, TeamMembershipService $memberships): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        $memberships->assertMember($team, $user);
        $memberships->leave($team, $user);

        return response()->json(['data' => ['left' => true]]);
    }

    /** @return array<string, mixed> */
    private function payload(Team $team, User $user): array
    {
        $team->load(['memberships.user:id,name,email', 'invitations' => fn ($query) => $query->whereNull('accepted_at')->latest()])->loadCount('monitorings');

        return ['id' => $team->id, 'name' => $team->name, 'description' => $team->description, 'monitoring_count' => $team->monitorings_count, 'can_manage' => $team->isAdmin($user), 'members' => $team->memberships->map(fn (TeamMembership $m) => ['id' => $m->id, 'name' => $m->user->name, 'email' => $m->user->email, 'role' => $m->role->value])->values(), 'invitations' => $team->isAdmin($user) ? $team->invitations->map(fn (TeamInvitation $i) => ['id' => $i->id, 'email' => $i->email, 'role' => $i->role->value, 'expires_at' => $i->expires_at?->toIso8601String()])->values() : []];
    }
}
