<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\External\TeamResource;
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
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $teams->map(
            fn (Team $team): array => TeamResource::make($team)->resolve($request)
        )->values()]);
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

        return response()->json(['data' => TeamResource::make($team->load('memberships.user'))->resolve($request)], 201);
    }

    public function show(Request $request, Team $team, TeamMembershipService $teamMembershipService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $teamMembershipService->assertMember($team, $user);

        return response()->json([
            'data' => TeamResource::make(
                $team->load(['memberships.user', 'invitations' => fn ($builder) => $builder->whereNull('accepted_at')])
                    ->loadCount('monitorings')
            )->resolve($request),
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

        return response()->json(['data' => TeamResource::make($team->refresh())->resolve($request)]);
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
