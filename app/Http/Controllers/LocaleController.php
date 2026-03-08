<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SupportedLanguage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Persist locale preference for anonymous and authenticated users.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(SupportedLanguage::values())],
        ]);

        $locale = $validated['locale'];

        if (Auth::check() && Auth::user()->locale !== $locale) {
            Auth::user()->update(['locale' => $locale]);
        }

        App::setLocale($locale);

        return back()->withCookie(
            cookie(
                SupportedLanguage::cookieName(),
                $locale,
                SupportedLanguage::cookieDurationMinutes()
            )
        );
    }
}
