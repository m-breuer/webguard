<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicCacheHeaders
{
    public function __construct(private readonly SetCacheHeaders $setCacheHeaders) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $this->setCacheHeaders->handle($request, $next, 'public;max_age=300;s_maxage=3600;etag');

        if ($response->isSuccessful()) {
            $response->setCache([
                'public' => true,
                'max_age' => 300,
                's_maxage' => 3600,
            ]);

            $response->headers->set('Vary', 'Accept-Language, Cookie', false);
        }

        return $response;
    }
}
