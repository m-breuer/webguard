<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\ServerInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;

/**
 * @group Public monitoring locations
 *
 * @unauthenticated
 */
final class PublicMonitoringLocationController extends Controller
{
    /**
     * List active monitoring locations and allowlist IP addresses.
     *
     * This public v1 endpoint returns only customer-safe location metadata and
     * globally routable source IP addresses. It intentionally omits instance
     * credentials, health, hostnames, and operational topology.
     *
     * @response {
     *   "data": [{
     *     "code": "de-1",
     *     "name": "Germany",
     *     "country_code": "DE",
     *     "region": "Europe",
     *     "allowlist_ips": ["1.1.1.1"],
     *     "active": true
     *   }],
     *   "meta": {"version": "1", "generated_at": "2026-08-18T12:00:00+00:00"}
     * }
     */
    public function __invoke(): JsonResponse
    {
        $locations = ServerInstance::query()
            ->active()
            ->whereNotNull('ip_address')
            ->orderBy('display_name')
            ->orderBy('code')
            ->get(['code', 'display_name', 'country_code', 'region', 'ip_address'])
            ->filter(static fn (ServerInstance $serverInstance): bool => is_string($serverInstance->ip_address) && filter_var(
                $serverInstance->ip_address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false)
            ->map(static function (ServerInstance $serverInstance): array {
                $ipAddress = (string) $serverInstance->ip_address;

                return [
                    'code' => $serverInstance->code,
                    'name' => $serverInstance->display_name ?? $serverInstance->code,
                    'country_code' => $serverInstance->country_code,
                    'region' => $serverInstance->region,
                    'allowlist_ips' => [$ipAddress],
                    'active' => true,
                ];
            })
            ->values();

        return response()
            ->json([
                'data' => $locations,
                'meta' => [
                    'version' => '1',
                    'generated_at' => Date::now()->toIso8601String(),
                ],
            ])
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=60');
    }
}
