<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Requests\Teams\TeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Teams\TeamInvitationService;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\RedirectResponse;

class TeamInvitationController extends Controller
{
    public function store(
        TeamInvitationRequest $teamInvitationRequest,
        Team $team,
        TeamMembershipService $teamMembershipService,
        TeamInvitationService $teamInvitationService
    ): RedirectResponse {
        /** @var User $user */
        $user = $teamInvitationRequest->user();
        $teamMembershipService->assertAdmin($team, $user);
        $validated = $teamInvitationRequest->validated();

        $teamInvitationService->invite(
            $team,
            $user,
            (string) $validated['email'],
            TeamRole::from((string) $validated['role'])
        );

        return back()->with('success', __('team.messages.invitation_sent'));
    }

    public function destroy(
        Team $team,
        TeamInvitation $teamInvitation,
        TeamMembershipService $teamMembershipService
    ): RedirectResponse {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertAdmin($team, $user);
        abort_unless($teamInvitation->team_id === $team->id, 404);

        $teamInvitation->delete();

        return back()->with('success', __('team.messages.invitation_revoked'));
    }
}
