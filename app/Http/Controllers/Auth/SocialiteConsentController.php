<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SocialiteConsentController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->hasPendingConsent($request)) {
            return to_route('login');
        }

        return view('auth.github-consent');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->hasPendingConsent($request)) {
            return to_route('login');
        }

        $request->validate([
            'terms' => ['accepted'],
        ]);

        $user = $this->resolveUserFromPendingConsent($request);

        if (! $user) {
            $this->clearPendingConsentState($request);

            return to_route('login')->withErrors(['socialite_error' => __('auth.github_consent.expired')]);
        }

        $user->forceFill([
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
        ])->save();

        $this->clearPendingConsentState($request);

        Auth::login($user);

        return to_route('dashboard');
    }

    private function hasPendingConsent(Request $request): bool
    {
        if ($request->session()->has(SocialiteController::SESSION_PENDING_EXISTING_USER_ID)) {
            return true;
        }

        return $request->session()->has(SocialiteController::SESSION_PENDING_GITHUB_USER);
    }

    private function clearPendingConsentState(Request $request): void
    {
        $request->session()->forget([
            SocialiteController::SESSION_PENDING_GITHUB_USER,
            SocialiteController::SESSION_PENDING_EXISTING_USER_ID,
        ]);
    }

    private function resolveUserFromPendingConsent(Request $request): ?User
    {
        $existingUserId = $request->session()->get(SocialiteController::SESSION_PENDING_EXISTING_USER_ID);
        if (is_string($existingUserId) && $existingUserId !== '') {
            return User::query()->find($existingUserId);
        }

        $pendingUser = $request->session()->get(SocialiteController::SESSION_PENDING_GITHUB_USER);
        if (! is_array($pendingUser)) {
            return null;
        }

        $email = (string) ($pendingUser['email'] ?? '');
        $githubId = (string) ($pendingUser['github_id'] ?? '');

        if ($email === '' || $githubId === '') {
            return null;
        }

        $updates = [
            'github_id' => $githubId,
            'github_token' => $pendingUser['github_token'] ?? null,
            'github_refresh_token' => $pendingUser['github_refresh_token'] ?? null,
        ];
        $avatar = $pendingUser['avatar'] ?? null;
        if (is_string($avatar) && $avatar !== '') {
            $updates['avatar'] = $avatar;
        }

        $user = User::query()
            ->where('github_id', $githubId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->update($updates);

            return $user;
        }

        $name = mb_trim((string) ($pendingUser['name'] ?? ''));
        if ($name === '') {
            $name = Str::before($email, '@');
        }

        return User::query()->create([
            'name' => $name !== '' ? $name : 'GitHub User',
            'email' => $email,
            ...$updates,
            'password' => null,
            'package_id' => Package::cheapest()?->id,
            'email_verified_at' => now(),
        ]);
    }
}
