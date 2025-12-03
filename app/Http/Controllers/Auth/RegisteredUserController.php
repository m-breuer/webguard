<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class RegisteredUserController
 *
 * Handles user registration, including displaying the registration form and storing new user accounts.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return View The registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  Request  $request  The HTTP request instance containing registration data.
     * @return RedirectResponse A redirect response after successful registration.
     *
     * @throws ValidationException If the registration validation fails.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $model = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => UserRole::REGULAR,
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($model));

        Auth::login($model);

        return redirect(route('dashboard', absolute: false));
    }
}
