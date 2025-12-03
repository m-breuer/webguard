<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class ConfirmablePasswordController
 *
 * Handles password confirmation for sensitive actions.
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     *
     * @return View The confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     *
     * @param  Request  $request  The HTTP request instance containing the password.
     * @return RedirectResponse A redirect response after successful password confirmation.
     *
     * @throws ValidationException If the password confirmation fails.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', Date::now()->getTimestamp());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
