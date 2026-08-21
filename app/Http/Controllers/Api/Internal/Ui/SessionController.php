<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SessionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $teams = $user->teamMemberships()
            ->with('team:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(static fn (TeamMembership $membership): array => [
                'id' => $membership->team_id,
                'name' => $membership->team->name,
                'role' => $membership->role->value,
            ])
            ->values();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'locale' => $user->locale,
                    'theme' => $user->theme,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'is_verified' => $user->hasVerifiedEmail(),
                ],
                'teams' => $teams,
                'csrf_endpoint' => route('sanctum.csrf-cookie', absolute: false),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'data' => [
                'authenticated' => false,
            ],
        ]);
    }
}
