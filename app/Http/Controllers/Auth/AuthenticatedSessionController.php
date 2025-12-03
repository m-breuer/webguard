<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class AuthenticatedSessionController
 *
 * Handles user authentication, including displaying the login form, authenticating users,
 * and destroying authenticated sessions.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return View The login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  LoginRequest  $loginRequest  The login request instance.
     * @return RedirectResponse A redirect response after successful authentication.
     */
    public function store(LoginRequest $loginRequest): RedirectResponse
    {
        $loginRequest->authenticate();

        $loginRequest->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return RedirectResponse A redirect response after destroying the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
