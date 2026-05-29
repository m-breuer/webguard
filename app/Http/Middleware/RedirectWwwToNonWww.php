<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToNonWww
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalUrl = (string) config('app.url');
        $canonicalHost = parse_url($canonicalUrl, PHP_URL_HOST);

        if (! is_string($canonicalHost) || $canonicalHost === '') {
            return $next($request);
        }

        if ($request->getHost() !== 'www.' . $canonicalHost) {
            return $next($request);
        }

        $canonicalScheme = parse_url($canonicalUrl, PHP_URL_SCHEME) ?: $request->getScheme();
        $canonicalPort = parse_url($canonicalUrl, PHP_URL_PORT);
        $redirectUrl = $canonicalScheme . '://' . $canonicalHost;

        if (is_int($canonicalPort)) {
            $redirectUrl .= ':' . $canonicalPort;
        }

        return redirect()->away($redirectUrl . $request->getRequestUri(), 301);
    }
}
