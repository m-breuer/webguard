<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Jobs\LogApiUsage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class TrackApiUsage
{
    /**
     * Middleware to validate API access for internal and external clients.
     *
     * This middleware permits requests if they are authenticated via a valid Sanctum token.
     * For authenticated external requests, it dispatches a job to log API usage.
     * Unauthorized requests receive a 403 response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();

            if ($user) {
                $key = 'api:' . $user->getAuthIdentifier();

                if (RateLimiter::tooManyAttempts($key, 5)) {
                    return response()->json([
                        'message' => 'Too many requests.',
                    ], 429)->header('Retry-After', (string) RateLimiter::availableIn($key));
                }

                RateLimiter::hit($key, 60);

                dispatch(new LogApiUsage((string) $user->getAuthIdentifier(), url()->current()));

                return $next($request);
            }
        }

        abort(403);
    }
}
