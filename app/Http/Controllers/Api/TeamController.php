<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Teams
 *
 * Manage teams and team ownership.
 */
class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $teams = Team::query()
            ->visibleTo($user)
            ->withCount(['memberships', 'monitorings'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $teams]);
    }

    public function store(Request $request, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team = $teamMembershipService->createTeam($user, $validated);

        return response()->json(['data' => $team->load('memberships.user')], 201);
    }

    public function show(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $teamMembershipService->assertMember($team, $user);

        return response()->json([
            'data' => $team->load(['memberships.user', 'invitations' => fn ($builder) => $builder->whereNull('accepted_at')])
                ->loadCount('monitorings'),
        ]);
    }

    public function update(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team->update($validated);

        return response()->json(['data' => $team->refresh()]);
    }

    public function destroy(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $teamMembershipService->assertAdmin($team, $user);

        $team->delete();

        return response()->json(status: 204);
    }
}
