<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicFeatureController extends Controller
{
    /**
     * @var array<string, array{key: string, category: string, related: list<string>}>
     */
    private const FEATURES = [
        'http-monitoring' => ['key' => 'http', 'category' => 'monitoring', 'related' => ['keyword-monitoring', 'status-code-monitoring', 'response-time-uptime']],
        'ping-monitoring' => ['key' => 'ping', 'category' => 'monitoring', 'related' => ['port-monitoring', 'multi-location-monitoring', 'notifications']],
        'keyword-monitoring' => ['key' => 'keyword', 'category' => 'monitoring', 'related' => ['http-monitoring', 'status-code-monitoring', 'notifications']],
        'port-monitoring' => ['key' => 'port', 'category' => 'monitoring', 'related' => ['ping-monitoring', 'notifications', 'maintenance-windows']],
        'heartbeat-monitoring' => ['key' => 'heartbeat', 'category' => 'monitoring', 'related' => ['notifications', 'api', 'response-time-uptime']],
        'server-health-monitoring' => ['key' => 'server_health', 'category' => 'monitoring', 'related' => ['api', 'notifications', 'weekly-digest']],
        'dns-record-monitoring' => ['key' => 'dns_record', 'category' => 'monitoring', 'related' => ['domain-expiration-monitoring', 'notifications', 'public-labels']],
        'domain-expiration-monitoring' => ['key' => 'domain_expiration', 'category' => 'monitoring', 'related' => ['ssl-certificate-monitoring', 'weekly-digest', 'notifications']],
        'ssl-certificate-monitoring' => ['key' => 'ssl', 'category' => 'monitoring', 'related' => ['domain-expiration-monitoring', 'notifications', 'weekly-digest']],
        'status-code-monitoring' => ['key' => 'http_expectations', 'category' => 'monitoring', 'related' => ['http-monitoring', 'keyword-monitoring', 'response-time-uptime']],
        'response-time-uptime' => ['key' => 'stats', 'category' => 'operations', 'related' => ['http-monitoring', 'public-labels', 'weekly-digest']],
        'multi-location-monitoring' => ['key' => 'multi_location', 'category' => 'operations', 'related' => ['http-monitoring', 'ping-monitoring', 'monitoring-locations']],
        'notifications' => ['key' => 'notifications', 'category' => 'operations', 'related' => ['weekly-digest', 'public-labels', 'maintenance-windows']],
        'weekly-digest' => ['key' => 'weekly_digest', 'category' => 'operations', 'related' => ['notifications', 'response-time-uptime', 'ssl-certificate-monitoring']],
        'maintenance-windows' => ['key' => 'maintenance_windows', 'category' => 'operations', 'related' => ['public-labels', 'public-status-pages', 'notifications']],
        'public-labels' => ['key' => 'public_labels', 'category' => 'sharing', 'related' => ['status-badges-widgets', 'public-status-pages', 'notifications']],
        'public-status-pages' => ['key' => 'public_status_pages', 'category' => 'sharing', 'related' => ['public-labels', 'maintenance-windows', 'notifications']],
        'status-badges-widgets' => ['key' => 'embeddable_widget', 'category' => 'sharing', 'related' => ['public-labels', 'api', 'public-status-pages']],
        'api' => ['key' => 'rest_api', 'category' => 'integrations', 'related' => ['server-health-monitoring', 'status-badges-widgets', 'notifications']],
    ];

    /**
     * @return array<string, array{key: string, category: string, related: list<string>}>
     */
    public static function features(): array
    {
        return self::FEATURES;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::FEATURES);
    }

    public function index(): View
    {
        return view('features.index', [
            'features' => self::FEATURES,
            'categories' => ['monitoring', 'operations', 'sharing', 'integrations'],
        ]);
    }

    public function show(string $feature): View|RedirectResponse
    {
        if ($feature === 'monitoring-locations') {
            return to_route('monitoring-locations');
        }

        abort_unless(array_key_exists($feature, self::FEATURES), 404);

        return view('features.show', [
            'slug' => $feature,
            'feature' => self::FEATURES[$feature],
            'features' => self::FEATURES,
        ]);
    }
}
