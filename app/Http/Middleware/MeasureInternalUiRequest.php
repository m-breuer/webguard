<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class MeasureInternalUiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = Str::uuid()->toString();
        $startedAt = hrtime(true);
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        try {
            $response = $next($request);
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $queryCount = count($connection->getQueryLog());

            $response->headers->set('X-Request-Id', $requestId);
            $response->headers->set('X-Query-Count', (string) $queryCount);
            $response->headers->set('X-Response-Bytes', (string) mb_strlen((string) $response->getContent()));
            $response->headers->set('Server-Timing', sprintf('app;dur=%.1f', $durationMs));

            return $response;
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
    }
}
