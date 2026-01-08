<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        abort_unless($request->user(), 403);

        foreach ($roles as $role) {
            $checkMethod = 'is' . ucfirst($role);
            if (method_exists($request->user(), $checkMethod) && $request->user()->$checkMethod()) {
                return $next($request);
            }
        }

        abort(403);
    }
}
