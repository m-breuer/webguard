<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamIndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $teams = Team::query()
            ->visibleTo($user)
            ->withCount(['memberships', 'monitorings'])
            ->with(['memberships' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderBy('name')
            ->get()
            ->map(static fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'member_count' => $team->memberships_count,
                'monitoring_count' => $team->monitorings_count,
                'role' => $team->memberships->first()?->role->value,
            ])
            ->values();

        return response()->json(['data' => ['teams' => $teams]]);
    }
}
