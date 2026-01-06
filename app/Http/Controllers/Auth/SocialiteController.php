<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleProviderCallback(): RedirectResponse
    {
        $githubUser = Socialite::driver('github')->user();

        if (empty($githubUser->getEmail())) {
            return redirect()->route('register')->withErrors(['socialite_error' => 'Could not retrieve your email address from GitHub. Please make your email public on your GitHub profile or register with email and password.']);
        }

        $user = User::query()->where('github_id', $githubUser->getId())->first();

        if ($user) {
            $user->update([
                'github_token' => $githubUser->token,
                'github_refresh_token' => $githubUser->refreshToken,
            ]);
        } else {
            $user = User::query()->where('email', $githubUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'github_id' => $githubUser->getId(),
                    'github_token' => $githubUser->token,
                    'github_refresh_token' => $githubUser->refreshToken,
                ]);
            } else {
                $user = User::query()->create([
                    'name' => $githubUser->getName(),
                    'email' => $githubUser->getEmail(),
                    'github_id' => $githubUser->getId(),
                    'github_token' => $githubUser->token,
                    'github_refresh_token' => $githubUser->refreshToken,
                    'password' => null,
                    'package_id' => Package::cheapest()?->id,
                    'email_verified_at' => now(),
                ]);
            }
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
