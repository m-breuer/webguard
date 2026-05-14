<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use Illuminate\Support\Str;

final class MonitoringPayload
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function prepareStore(array $validated): array
    {
        $type = MonitoringType::tryFrom((string) ($validated['type'] ?? ''));

        if ($type === MonitoringType::DOMAIN_EXPIRATION) {
            return self::applyDomainDefaults($validated);
        }

        if (in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true)) {
            $validated['expected_http_statuses'] = HttpStatusCodeRanges::normalize($validated['expected_http_statuses'] ?? null);

            return $validated;
        }

        if ($type !== MonitoringType::HEARTBEAT) {
            $validated['expected_http_statuses'] = null;

            return $validated;
        }

        $heartbeatToken = (string) Str::ulid();

        $validated['heartbeat_token'] = $heartbeatToken;
        $validated['target'] = route('monitorings.heartbeat.ping', ['token' => $heartbeatToken]);

        return self::applyNonHttpDefaults($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function prepareUpdate(array $validated, Monitoring $monitoring): array
    {
        if ($monitoring->type === MonitoringType::DOMAIN_EXPIRATION) {
            $validated['target'] = $monitoring->target;

            return self::applyDomainDefaults($validated);
        }

        if (in_array($monitoring->type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true)) {
            $validated['expected_http_statuses'] = HttpStatusCodeRanges::normalize($validated['expected_http_statuses'] ?? null);

            return $validated;
        }

        if (! $monitoring->isHeartbeat()) {
            $validated['expected_http_statuses'] = null;

            return $validated;
        }

        $validated['target'] = $monitoring->target;

        return self::applyNonHttpDefaults($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private static function applyDomainDefaults(array $validated): array
    {
        $validated['target'] = mb_strtolower(mb_trim((string) $validated['target']));

        return self::applyNonHttpDefaults($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private static function applyNonHttpDefaults(array $validated): array
    {
        $validated['timeout'] = 5;
        $validated['http_method'] = null;
        $validated['expected_http_statuses'] = null;
        $validated['http_headers'] = null;
        $validated['http_body'] = null;
        $validated['auth_username'] = null;
        $validated['auth_password'] = null;
        $validated['port'] = null;
        $validated['keyword'] = null;

        return $validated;
    }
}
