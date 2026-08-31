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
                ? $this->frontendUrl('/login')
                : $this->frontendUrl('/register', ['mode' => 'register', 'email' => $teamInvitation->email]);

            return redirect()->guest($targetRoute)
                ->with('status', __('team.messages.login_to_accept'));
        }

        /** @var User $user */
        $user = $request->user();
        $team = $teamInvitationService->accept($token, $user);

        return redirect()->away($this->frontendUrl('/teams/' . $team->getRouteKey()))
            ->with('success', __('team.messages.invitation_accepted'));
    }

    /**
     * @param  array<string, string>  $query
     */
    private function frontendUrl(string $path, array $query = []): string
    {
        $url = mb_rtrim((string) config('app.url'), '/') . $path;

        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }
}
