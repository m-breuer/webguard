<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireExternalApiAbility
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('external_api.enforce_token_abilities')) {
            return $next($request);
        }

        $ability = $request->isMethodSafe() ? 'external:read' : 'external:write';

        abort_unless($request->user()?->tokenCan($ability), 403);

        return $next($request);
    }
}
