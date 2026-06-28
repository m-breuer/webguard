<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Teams\TeamInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamInvitationAcceptController extends Controller
{
    public function __invoke(
        string $token,
        Request $request,
        TeamInvitationService $teamInvitationService
    ): RedirectResponse {
        $teamInvitation = $teamInvitationService->findPendingByToken($token);

        if (! $request->user()) {
            $targetRoute = User::query()->where('email', $teamInvitation->email)->exists()
                ? route('login')
                : route('register', ['mode' => 'register', 'email' => $teamInvitation->email]);

            return redirect()->guest($targetRoute)
                ->with('status', __('team.messages.login_to_accept'));
        }

        /** @var User $user */
        $user = $request->user();
        $team = $teamInvitationService->accept($token, $user);

        return to_route('teams.show', $team)
            ->with('success', __('team.messages.invitation_accepted'));
    }
}
