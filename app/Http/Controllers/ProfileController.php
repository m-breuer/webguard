<?php

namespace App\Http\Controllers;

use App\Enums\SupportedLanguage;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Class ProfileController
 *
 * Handles profile management including editing, updating, and deleting the authenticated user's account.
 */
class ProfileController extends Controller
{
    /**
     * Display the form to edit the user's profile.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View The view for editing the user's profile.
     */
    public function edit(Request $request): View
    {
        $filteredLanguages = SupportedLanguage::toArray();

        return view('profile.edit', [
            'user' => $request->user(),
            'token' => $request->user()->currentAccessToken(),
            'languages' => $filteredLanguages,
        ]);
    }

    /**
     * Update the user's profile information and reset email verification if needed.
     *
     * @param  ProfileRequest  $profileRequest  The request containing validated profile data.
     * @return RedirectResponse A redirect response after updating the profile.
     */
    public function update(ProfileRequest $profileRequest): RedirectResponse
    {
        $profileRequest->user()->fill($profileRequest->validated());

        if ($profileRequest->user()->isDirty('email')) {
            $profileRequest->user()->email_verified_at = null;
        }

        $profileRequest->user()->save();

        // Update the theme in the session immediately after saving
        session(['theme' => $profileRequest->user()->theme]);

        return to_route('profile.edit')->with('success', __('profile.messages.profile_updated'));
    }

    /**
     * Delete the authenticated user's account after verifying their password.
     *
     * This method validates the user's current password using a named error bag ("userDeletion"),
     * logs the user out, deletes the account, and invalidates the session.
     *
     * @param  Request  $deleteUserRequest  The incoming HTTP request containing the password confirmation.
     * @return RedirectResponse Redirects to the home page after account deletion.
     */
    public function destroy(DeleteUserRequest $deleteUserRequest): RedirectResponse
    {
        $user = $deleteUserRequest->user();

        Auth::logout();

        $user->delete();

        $deleteUserRequest->session()->invalidate();
        $deleteUserRequest->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Generate a new API token.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return RedirectResponse A redirect response after generating the token.
     */
    public function apiGenerateToken(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->tokens()->delete();

        $user->createToken('api-access');

        return to_route('profile.edit', ['#api-token']);
    }

    /**
     * Revoke the API token.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return RedirectResponse A redirect response after revoking the token.
     */
    public function apiRevokeToken(Request $request): RedirectResponse
    {
        $request->user()->tokens()->delete();

        return back()->with('success', __('api.configuration.messages.tokens_deleted'));
    }
}
