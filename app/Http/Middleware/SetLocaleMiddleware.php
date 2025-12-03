<?php

namespace App\Http\Middleware;

use App\Enums\SupportedLanguage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            App::setLocale($user->locale);

            // Set theme based on user preference
            if ($user->theme === 'dark') {
                session(['theme' => 'dark']);
            } elseif ($user->theme === 'light') {
                session(['theme' => 'light']);
            } else { // system
                session(['theme' => 'system']);
            }
        } else {
            // Fallback to default locale if user is not logged in
            App::setLocale(SupportedLanguage::default()->value);
            // Fallback to system theme if user is not logged in
            session(['theme' => 'system']);
        }

        return $next($request);
    }
}
