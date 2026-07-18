<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Class PasswordController
 *
 * Handles updating the authenticated user's password.
 */
class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * @param  Request  $request  The HTTP request instance containing the new password data.
     * @return RedirectResponse A redirect response after updating the password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', __('profile.form.saved'));
    }
}
