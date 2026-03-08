<?php

declare(strict_types=1);

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
            $locale = SupportedLanguage::isSupported($user->locale)
                ? $user->locale
                : $this->resolveLocaleFromCookieOrHeader($request);

            App::setLocale($locale);

            // Set theme based on user preference
            if ($user->theme === 'dark') {
                session(['theme' => 'dark']);
            } elseif ($user->theme === 'light') {
                session(['theme' => 'light']);
            } else { // system
                session(['theme' => 'system']);
            }
        } else {
            App::setLocale($this->resolveLocaleFromCookieOrHeader($request));
            // Fallback to system theme if user is not logged in
            session(['theme' => 'system']);
        }

        return $next($request);
    }

    private function resolveLocaleFromCookieOrHeader(Request $request): string
    {
        $cookieLocale = $request->cookie(SupportedLanguage::cookieName());
        if (SupportedLanguage::isSupported($cookieLocale)) {
            return $cookieLocale;
        }

        $preferredLanguage = $request->getPreferredLanguage(SupportedLanguage::values());
        if (SupportedLanguage::isSupported($preferredLanguage)) {
            return $preferredLanguage;
        }

        return SupportedLanguage::default()->value;
    }
}
