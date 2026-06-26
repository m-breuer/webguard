<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Teams\TeamRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('teams.index', [
            'teams' => Team::query()
                ->visibleTo($user)
                ->withCount(['memberships', 'monitorings'])
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        abort_if(auth()->user()->isDemo(), 403);

        return view('teams.create');
    }

    public function store(TeamRequest $teamRequest, TeamMembershipService $teamMembershipService): RedirectResponse
    {
        /** @var User $user */
        $user = $teamRequest->user();
        $team = $teamMembershipService->createTeam($user, $teamRequest->validated());

        return to_route('teams.show', $team)
            ->with('success', __('team.messages.created'));
    }

    public function show(Team $team, TeamMembershipService $teamMembershipService): View
    {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertMember($team, $user);

        return view('teams.show', [
            'team' => $team->load([
                'memberships.user',
                'invitations' => fn ($builder) => $builder->whereNull('accepted_at')->latest(),
            ])->loadCount('monitorings'),
            'isTeamAdmin' => $team->isAdmin($user),
        ]);
    }

    public function edit(Team $team, TeamMembershipService $teamMembershipService): View
    {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertAdmin($team, $user);

        return view('teams.edit', ['team' => $team]);
    }

    public function update(TeamRequest $teamRequest, Team $team, TeamMembershipService $teamMembershipService): RedirectResponse
    {
        /** @var User $user */
        $user = $teamRequest->user();
        $teamMembershipService->assertAdmin($team, $user);

        $team->update($teamRequest->validated());

        return to_route('teams.show', $team)
            ->with('success', __('team.messages.updated'));
    }

    public function destroy(Team $team, TeamMembershipService $teamMembershipService): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertAdmin($team, $user);

        $team->delete();

        return to_route('teams.index')
            ->with('success', __('team.messages.deleted'));
    }
}
