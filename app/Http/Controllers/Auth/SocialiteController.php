<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public const SESSION_PENDING_GITHUB_USER = 'auth.github.pending_user';

    public const SESSION_PENDING_EXISTING_USER_ID = 'auth.github.pending_existing_user_id';

    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleProviderCallback(Request $request): RedirectResponse
    {
        $githubUser = Socialite::driver('github')->user();
        $email = (string) ($githubUser->getEmail() ?? '');
        $githubId = (string) ($githubUser->getId() ?? '');

        if ($email === '') {
            return to_route('register')->withErrors(['socialite_error' => 'Could not retrieve your email address from GitHub. Please make your email public on your GitHub profile or register with email and password.']);
        }

        if ($githubId === '') {
            return to_route('register')->withErrors(['socialite_error' => 'Could not retrieve your GitHub account identifier. Please try again or register with email and password.']);
        }

        $user = User::query()
            ->where('github_id', $githubId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $updates = [
                'github_id' => $githubId,
                'github_token' => $githubUser->token,
                'github_refresh_token' => $githubUser->refreshToken,
            ];
            $avatar = $githubUser->getAvatar();
            if (is_string($avatar) && $avatar !== '') {
                $updates['avatar'] = $avatar;
            }
            $user->update($updates);

            if ($user->terms_accepted_at && $user->privacy_accepted_at) {
                $this->clearPendingConsentState($request);
                Auth::login($user);

                return to_route('dashboard');
            }

            $request->session()->put(self::SESSION_PENDING_EXISTING_USER_ID, $user->id);
            $request->session()->forget(self::SESSION_PENDING_GITHUB_USER);

            return to_route('github.consent.create');
        }

        $request->session()->put(self::SESSION_PENDING_GITHUB_USER, [
            'name' => (string) ($githubUser->getName() ?? ''),
            'email' => $email,
            'github_id' => $githubId,
            'github_token' => $githubUser->token,
            'github_refresh_token' => $githubUser->refreshToken,
            'avatar' => $githubUser->getAvatar(),
        ]);
        $request->session()->forget(self::SESSION_PENDING_EXISTING_USER_ID);

        return to_route('github.consent.create');
    }

    private function clearPendingConsentState(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_PENDING_GITHUB_USER,
            self::SESSION_PENDING_EXISTING_USER_ID,
        ]);
    }
}
