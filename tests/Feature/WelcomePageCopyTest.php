<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
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
                    'Heartbeat Monitoring',
                    'Monitor cronjobs, workers, and background processes',
                    'HTTP, Ping, Keyword, Port, Heartbeat, Server Health, DNS, SSL, and domains',
                    'Expected HTTP Status Ranges',
                    'Define accepted status codes or ranges such as 200-299, 301, and 302',
                    'Weekly Monitoring Digest',
                    'weekly email summaries with uptime, incidents, longest downtime',
                    'Domain Expiration Checks',
                    'send proactive renewal warnings',
                    'Server Health Monitoring',
                    'per-monitor thresholds before health reports are marked down',
                    'DNS Record Monitoring',
                    'Track expected A, AAAA, CNAME, MX, TXT, NS, SOA, and CAA records',
                    'Groups',
                    'Group related monitors, filter busy monitoring lists',
                    'publish groups as status pages',
                    'Slack, Telegram, Discord, Microsoft Teams, webhooks',
                    'Public Status Pages',
                    'component-based status pages with recent incidents, manual incident updates, subscriber emails',
                    'compact SLA badge',
                    'REST API and Integrations',
                    'token-based API access and the API reference',
                    'method, header, body, auth, and status-code validation',
                    'HTTP, Ping, Keyword, Port, Heartbeat, Server Health, DNS Record',
                ],
            ],
            'german product surfaces' => [
                'de',
                [
                    'Heartbeat Monitoring',
                    'Überwachen Sie Cronjobs, Worker und Hintergrundprozesse',
                    'HTTP, Ping, Keyword, Port, Heartbeat, Server-Zustand, DNS, SSL und Domains',
                    'Erwartete HTTP-Statusbereiche',
                    'Definieren Sie akzeptierte Statuscodes oder Bereiche wie 200-299, 301 und 302',
                    'Wöchentlicher Monitoring-Bericht',
                    'wöchentliche E-Mail-Zusammenfassungen mit Uptime, Incidents, längster Downtime',
                    'Domain-Ablaufprüfungen',
                    'proaktive Verlängerungswarnungen',
                    'Server-Zustand-Monitoring',
                    'pro Monitor eigene Schwellen',
                    'DNS-Eintragsmonitoring',
                    'A-, AAAA-, CNAME-, MX-, TXT-, NS-, SOA- und CAA-Einträge',
                    'Gruppen',
                    'Gruppieren Sie zusammengehörige Monitore',
                    'Statusseite',
                    'Slack, Telegram, Discord, Microsoft Teams, Webhooks',
                    'Öffentliche Statusseiten',
                    'komponentenbasierte Statusseiten mit aktuellen Incidents, manuellen Updates, E-Mail-Abos',
                    'kompaktes SLA-Badge',
                    'REST API und Integrationen',
                    'tokenbasierten API-Zugriff und die API-Referenz',
                    'Methode, Headern, Body, Authentifizierung und Statuscode-Prüfung',
                    'HTTP, Ping, Keyword, Port, Heartbeat, Server-Zustand, DNS-Eintrag',
                    'Jetzt starten',
                    'Demo-Zugang',
                    '24/7 Checks alle 5 Minuten',
                ],
                [
                    'API-Doku lesen',
                    'Kostenfrei starten',
                    'Demo-Zugang nutzen',
                    'flexiblen Intervallen',
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
}
