<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonException;

final class DnsRecordExpectation
{
    /**
     * @return list<string>
     */
    public static function recordTypes(): array
    {
        return ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SOA', 'CAA'];
    }

    public static function normalizeRecordType(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $recordType = mb_strtoupper(mb_trim((string) $value));

        return $recordType === '' ? null : $recordType;
    }

    /**
     * @return list<string>
     */
    public static function normalizeValues(mixed $values, ?string $recordType): array
    {
        $items = self::parseValues($values);
        $normalized = [];

        foreach ($items as $item) {
            if (! is_scalar($item)) {
                throw new InvalidArgumentException('DNS expected values must be scalar.');
            }

            $value = self::normalizeValue((string) $item, $recordType);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

        return $normalized;
    }

    /**
     * @return list<mixed>
     */
    private static function parseValues(mixed $values): array
    {
        if ($values === null) {
            return [];
        }

        if (is_array($values)) {
            return array_values($values);
        }

        if (! is_string($values)) {
            throw new InvalidArgumentException('DNS expected values must be provided as text or an array.');
        }

        $trimmed = mb_trim($values);

        if ($trimmed === '') {
            return [];
        }

        if (str_starts_with($trimmed, '[')) {
            try {
                $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('DNS expected values must be valid JSON.', previous: $exception);
            }

            if (! is_array($decoded)) {
                throw new InvalidArgumentException('DNS expected values JSON must decode to an array.');
            }

            return array_values($decoded);
        }

        return preg_split('/\R/u', $trimmed) ?: [];
    }

    private static function normalizeValue(string $value, ?string $recordType): string
    {
        $value = preg_replace('/\s+/u', ' ', mb_trim($value)) ?? '';

        if ($value === '') {
            return '';
        }

        return match ($recordType) {
            'A' => self::normalizeIpv4($value),
            'AAAA' => self::normalizeIpv6($value),
            'CNAME', 'NS' => self::normalizeHostnameValue($value),
            'MX' => self::normalizeMxValue($value),
            default => $value,
        };
    }

    private static function normalizeIpv4(string $value): string
    {
        if (! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new InvalidArgumentException('DNS A records must be IPv4 addresses.');
        }

        return $value;
    }

    private static function normalizeIpv6(string $value): string
    {
        if (! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new InvalidArgumentException('DNS AAAA records must be IPv6 addresses.');
        }

        return mb_strtolower($value);
    }

    private static function normalizeHostnameValue(string $value): string
    {
        return mb_strtolower(preg_replace('/\.+$/u', '', $value) ?? $value);
    }

    private static function normalizeMxValue(string $value): string
    {
        $parts = explode(' ', $value, 2);

        if (count($parts) === 2 && ctype_digit($parts[0])) {
            return sprintf('%d %s', (int) $parts[0], self::normalizeHostnameValue($parts[1]));
        }

        return self::normalizeHostnameValue($value);
    }
}
