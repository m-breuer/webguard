<?php

declare(strict_types=1);

namespace App\Support;

function dns_get_record(string $hostname, int $type): array|false
{
    $records = $GLOBALS['publicly_routable_url_dns_records'] ?? [];

    if (is_array($records) && array_key_exists($hostname, $records)) {
        return $records[$hostname];
    }

    return [['ip' => '93.184.216.34']];
}
