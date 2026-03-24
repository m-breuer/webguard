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
        } else {
            App::setLocale($this->resolveLocaleFromCookieOrHeader($request));
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
