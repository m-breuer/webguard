<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class RequestCorrelationId
{
    public static function for(Request $request): string
    {
        $requestId = $request->header('X-Request-Id');

        if (is_string($requestId) && self::isTrustedFormat($requestId)) {
            return $requestId;
        }

        return Str::uuid()->toString();
    }

    private static function isTrustedFormat(string $requestId): bool
    {
        return preg_match('/\A[\da-f]{32}\z/i', $requestId) === 1
            || Str::isUuid($requestId);
    }
}
