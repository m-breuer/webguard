<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Requests\Teams\TeamMembershipRequest;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\RedirectResponse;

class TeamMemberController extends Controller
{
    public function update(
        TeamMembershipRequest $teamMembershipRequest,
        Team $team,
        TeamMembership $membership,
        TeamMembershipService $teamMembershipService
    ): RedirectResponse {
        /** @var User $user */
        $user = $teamMembershipRequest->user();
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($membership->team_id === $team->id, 404);
        $validated = $teamMembershipRequest->validated();

        $teamMembershipService->changeRole($membership, TeamRole::from((string) $validated['role']));

        return back()->with('success', __('team.messages.member_updated'));
    }

    public function destroy(
        Team $team,
        TeamMembership $membership,
        TeamMembershipService $teamMembershipService
    ): RedirectResponse {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($membership->team_id === $team->id, 404);

        $teamMembershipService->remove($membership);

        return back()->with('success', __('team.messages.member_removed'));
    }

    public function leave(Team $team, TeamMembershipService $teamMembershipService): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertMember($team, $user);
        $teamMembershipService->leave($team, $user);

        return to_route('teams.index')->with('success', __('team.messages.left'));
    }
}
