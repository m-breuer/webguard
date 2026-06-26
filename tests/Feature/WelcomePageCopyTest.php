<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use App\Http\Controllers\PublicFeatureController;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomePageCopyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: list<string>, 2?: list<string>}>
     */
    public static function localizedCopyProvider(): array
    {
        return [
            'english product surfaces' => [
                'en',
                [
                    'WebGuard delivers professional monitoring for teams and individual projects.',
                    'Website & API Monitoring',
                    'HTTP checks, keyword validation, and expected status codes',
                    'Infrastructure Checks',
                    'Ping, ports, and DNS records',
                    'Cronjobs & Alerts',
                    'Heartbeat monitoring, notification channels, and weekly reports',
                    'Status Pages',
                    'Public status pages, public labels, and SLA badges',
                    'Integrations & API',
                    'REST API, server health reports, and groups',
                    'Status communication and API access are first-class public surfaces',
                    'REST API and Integrations',
                    '24/7 checks every 5 minutes',
                ],
                [
                    'trusted by developers',
                    'Trusted by developers',
                ],
            ],
            'german product surfaces' => [
                'de',
                [
                    'WebGuard bietet professionelles Monitoring für Teams und Einzelprojekte.',
                    'Website & API Monitoring',
                    'HTTP-Checks, Keyword-Prüfungen und erwartete Statuscodes',
                    'Infrastruktur-Checks',
                    'Ping, Ports und DNS-Einträge',
                    'Cronjobs & Alerts',
                    'Heartbeat-Monitoring, Benachrichtigungskanäle und Wochenberichte',
                    'Status Pages',
                    'Öffentliche Statusseiten, Public Labels und SLA-Badges',
                    'Integrationen & API',
                    'REST API, Server-Health-Reports und Gruppen',
                    'Statuskommunikation und API-Zugriff sind sichtbare öffentliche Flächen',
                    'REST API und Integrationen',
                    'Jetzt starten',
                    'Demo-Zugang',
                    '24/7 Checks alle 5 Minuten',
                ],
                [
                    'API-Doku lesen',
                    'Kostenfrei starten',
                    'Demo-Zugang nutzen',
                    'flexiblen Intervallen',
                    'trusted by developers',
                    'Trusted by developers',
                ],
            ],
        ];
    }

    #[DataProvider('localizedCopyProvider')]
    public function test_welcome_page_renders_localized_product_copy(
        string $locale,
        array $expectedCopy,
        array $unexpectedCopy = []
    ): void {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get(route('welcome'));

        $testResponse->assertOk();

        foreach ($expectedCopy as $copy) {
            $testResponse->assertSeeText($copy);
        }

        foreach ($unexpectedCopy as $copy) {
            $testResponse->assertDontSeeText($copy);
        }
    }

    public function test_welcome_page_renders_configured_monitoring_interval_copy(): void
    {
        $expectations = [
            ['en', 5, '5 minutes'],
            ['en', 1, '1 minute'],
            ['de', 5, '5 Minuten'],
            ['de', 1, '1 Minute'],
        ];

        foreach ($expectations as [$locale, $interval, $expectedCopy]) {
            config(['monitoring.interval' => $interval]);

            $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get(route('welcome'));

            $testResponse->assertOk();
            $testResponse->assertSeeText($expectedCopy);
        }
    }

    public function test_welcome_page_updates_monitoring_interval_copy_for_each_request(): void
    {
        config(['monitoring.interval' => 5]);

        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), 'de')->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('5 Minuten');

        config(['monitoring.interval' => 1]);

        $secondResponse = $this->withCookie(SupportedLanguage::cookieName(), 'de')->get(route('welcome'));

        $secondResponse->assertOk();
        $secondResponse->assertSeeText('1 Minute');
    }

    public function test_welcome_page_links_each_feature_cluster_to_public_feature_pages(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();

        $expectedClusterSlugs = [
            'http-monitoring',
            'keyword-monitoring',
            'status-code-monitoring',
            'ping-monitoring',
            'port-monitoring',
            'dns-record-monitoring',
            'heartbeat-monitoring',
            'notifications',
            'weekly-digest',
            'public-status-pages',
            'public-labels',
            'status-badges',
            'api',
            'server-health-monitoring',
            'monitoring-groups',
        ];

        foreach ($expectedClusterSlugs as $slug) {
            $this->assertContains($slug, PublicFeatureController::slugs());
            $testResponse->assertSeeHtml(route('public-features.show', $slug));
        }

        $testResponse->assertSeeHtml(sprintf('alt="%s"', e(__('welcome.visuals.photos.hero_alt'))));
        $testResponse->assertSeeHtml(sprintf('alt="%s"', e(__('welcome.visuals.photos.status_alt'))));
        $testResponse->assertSeeHtml(sprintf('alt="%s"', e(__('welcome.visuals.photos.workflow_alt'))));
    }
}
