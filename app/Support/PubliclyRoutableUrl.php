<?php

declare(strict_types=1);

namespace App\Support;

class PubliclyRoutableUrl
{
    public static function allows(string $url, bool $resolveDns = false): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! is_string($host) || ! is_string($scheme)) {
            return false;
        }

        if (! in_array(mb_strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        return self::isPublicHost($host, $resolveDns);
    }

    private static function isPublicHost(string $host, bool $resolveDns): bool
    {
        $host = mb_strtolower(trim($host, "[] \t\n\r\0\x0B."));

        if ($host === '' || $host === 'localhost') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        if (
            ! str_contains($host, '.')
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
        ) {
            return false;
        }

        if (! $resolveDns) {
            return true;
        }

        $addresses = self::resolveHost($host);

        if ($addresses === []) {
            return true;
        }

        foreach ($addresses as $address) {
            if (! self::isPublicIp($address)) {
                return false;
            }
        }

        return true;
    }

    private static function isPublicIp(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @return list<string>
     */
    private static function resolveHost(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            foreach (['ip', 'ipv6'] as $key) {
                if (isset($record[$key]) && is_string($record[$key])) {
                    $addresses[] = $record[$key];
                }
            }
        }

        return array_values(array_unique($addresses));
    }
}
