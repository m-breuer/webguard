<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeHeartbeatCopyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function heartbeatCopyProvider(): array
    {
        return [
            'english' => ['en', 'Heartbeat Monitoring', 'Monitor cronjobs, workers, and background processes'],
            'german' => ['de', 'Heartbeat Monitoring', 'Überwachen Sie Cronjobs, Worker und Hintergrundprozesse'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function heartbeatMetadataProvider(): array
    {
        return [
            'english' => [
                'en',
                'WebGuard - Free Monitoring for Websites, APIs, Servers, Ports, and Cronjobs',
                'WebGuard is free-to-use monitoring software for HTTP, Ping, Keyword, Port, and Heartbeat checks with notifications, SSL expiry tracking, uptime insights, and public status pages.',
                'free monitoring software, uptime monitoring, website monitoring, ping monitoring, keyword monitoring, port monitoring, heartbeat monitoring, cronjob monitoring, SSL expiry monitoring, status page, incident alerts',
                'Track availability and performance with HTTP, Ping, Keyword, Port, and Heartbeat monitoring, clear notifications, and easy-to-read uptime reporting.',
            ],
            'german' => [
                'de',
                'WebGuard - Kostenfreies Monitoring für Websites, APIs, Server, Ports und Cronjobs',
                'WebGuard ist eine kostenfrei nutzbare Monitoring-Software für HTTP-, Ping-, Keyword-, Port- und Heartbeat-Checks mit Benachrichtigungen, SSL-Ablaufkontrolle, Uptime-Auswertungen und öffentlichen Statusseiten.',
                'Kostenfreies Monitoring, Uptime Monitoring, Website Monitoring, Ping Monitoring, Keyword Monitoring, Port Monitoring, Heartbeat Monitoring, Cronjob Monitoring, SSL Ablauf, Statusseite, Incident Benachrichtigung',
                'Überwachen Sie Verfügbarkeit und Performance mit HTTP-, Ping-, Keyword-, Port- und Heartbeat-Checks, klaren Benachrichtigungen und nachvollziehbaren Uptime-Reports.',
            ],
        ];
    }

    #[DataProvider('heartbeatCopyProvider')]
    public function test_it_renders_heartbeat_monitoring_on_the_welcome_page(
        string $locale,
        string $expectedTitle,
        string $expectedText
    ): void {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeText($expectedTitle);
        $testResponse->assertSeeText($expectedText);
    }

    #[DataProvider('heartbeatMetadataProvider')]
    public function test_it_includes_heartbeat_monitoring_in_marketing_metadata(
        string $locale,
        string $expectedTitle,
        string $expectedDescription,
        string $expectedKeywords,
        string $expectedOgDescription
    ): void {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('<title>' . $expectedTitle . '</title>');
        $testResponse->assertSeeHtml('<meta name="description" content="' . $expectedDescription . '">');
        $testResponse->assertSeeHtml('<meta name="keywords" content="' . $expectedKeywords . '">');
        $testResponse->assertSeeHtml('<meta property="og:description" content="' . $expectedOgDescription . '">');
        $testResponse->assertSeeHtml('<meta name="twitter:description" content="' . $expectedOgDescription . '">');
    }
}
