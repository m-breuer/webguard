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
    public function index(Request $request, TeamMembershipService $teamMembershipService): View
    {
        /** @var User $user */
        $user = $request->user();
        $modalForm = $request->input('modal');
        $modalTeam = null;

        if ($modalForm === 'team-create') {
            abort_if($user->isDemo(), 403);
        } elseif ($modalForm === 'team-edit' && $request->filled('team')) {
            $modalTeam = Team::query()->findOrFail($request->string('team')->toString());
            $teamMembershipService->assertAdmin($modalTeam, $user);
        }

        return view('teams.index', [
            'teams' => Team::query()
                ->visibleTo($user)
                ->withCount(['memberships', 'monitorings'])
                ->orderBy('name')
                ->paginate(10),
            'modalForm' => $modalForm,
            'modalTeam' => $modalTeam,
        ]);
    }

    public function create(Request $request): View
    {
        abort_if(auth()->user()->isDemo(), 403);

        if ($request->boolean('modal')) {
            return view('teams._modal-form', [
                'action' => route('teams.store'),
                'modalForm' => 'team-create',
            ]);
        }

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

    public function edit(Request $request, Team $team, TeamMembershipService $teamMembershipService): View
    {
        /** @var User $user */
        $user = auth()->user();
        $teamMembershipService->assertAdmin($team, $user);

        if ($request->boolean('modal')) {
            return view('teams._modal-form', [
                'action' => route('teams.update', $team),
                'team' => $team,
                'modalForm' => 'team-edit',
            ]);
        }

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
