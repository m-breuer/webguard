<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class HttpStatusCodeRanges
{
    public const DEFAULT = '200-299';

    public static function normalize(?string $value): string
    {
        $value = mb_trim((string) $value);

        if ($value === '') {
            return self::DEFAULT;
        }

        $parts = array_map(
            static fn (string $part): string => mb_trim($part),
            explode(',', $value)
        );

        $normalized = [];

        foreach ($parts as $part) {
            throw_if($part === '', InvalidArgumentException::class, 'Empty HTTP status code segment.');

            if (preg_match('/^\d{3}$/', $part) === 1) {
                $statusCode = (int) $part;
                self::ensureStatusCode($statusCode);
                $normalized[] = (string) $statusCode;

                continue;
            }

            throw_if(preg_match('/^(\d{3})\s*-\s*(\d{3})$/', $part, $matches) !== 1, InvalidArgumentException::class, 'Invalid HTTP status code segment.');

            $start = (int) $matches[1];
            $end = (int) $matches[2];

            self::ensureStatusCode($start);
            self::ensureStatusCode($end);

            throw_if($start > $end, InvalidArgumentException::class, 'HTTP status code ranges must be ascending.');

            $normalized[] = $start === $end ? (string) $start : sprintf('%d-%d', $start, $end);
        }

        return implode(',', $normalized);
    }

    public static function contains(string $ranges, int $statusCode): bool
    {
        self::ensureStatusCode($statusCode);

        foreach (explode(',', self::normalize($ranges)) as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part, 2));

                if ($statusCode >= $start && $statusCode <= $end) {
                    return true;
                }

                continue;
            }

            if ($statusCode === (int) $part) {
                return true;
            }
        }

        return false;
    }

    private static function ensureStatusCode(int $statusCode): void
    {
        throw_if($statusCode < 100 || $statusCode > 599, InvalidArgumentException::class, 'HTTP status codes must be between 100 and 599.');
    }
}
