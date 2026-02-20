<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ServerInstance;
use Closure;
use Illuminate\Http\Request;

class AuthenticateInstance
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $instanceCode = (string) $request->header('X-INSTANCE-CODE', '');
        $apiKey = (string) $request->header('X-API-KEY', '');

        if ($instanceCode === '' || $apiKey === '') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $instance = ServerInstance::query()
            ->active()
            ->where('code', $instanceCode)
            ->first();

        if (! $instance || ! $instance->verifyApiKey($apiKey)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('authenticated_instance_code', $instance->code);

        return $next($request);
    }
}
