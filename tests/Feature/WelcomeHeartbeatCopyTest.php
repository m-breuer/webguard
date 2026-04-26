<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeHeartbeatCopyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function heartbeatCopyProvider(): array
    {
        return [
            'english' => [
                'en',
                'Heartbeat Monitoring',
                'Monitor cronjobs, workers, and background processes',
                'HTTP, Ping, Keyword, Port, Heartbeat, and digests',
                'HTTP, Ping, Keyword, Port, and Heartbeat checks with expected HTTP status ranges, weekly digests, notifications',
            ],
            'german' => [
                'de',
                'Heartbeat Monitoring',
                'Überwachen Sie Cronjobs, Worker und Hintergrundprozesse',
                'HTTP, Ping, Keyword, Port, Heartbeat und Berichte',
                'HTTP-, Ping-, Keyword-, Port- und Heartbeat-Checks mit erwarteten HTTP-Statusbereichen, Wochenberichten, Benachrichtigungen',
            ],
        ];
    }

    #[DataProvider('heartbeatCopyProvider')]
    public function test_it_renders_heartbeat_monitoring_on_the_welcome_page(
        string $locale,
        string $expectedTitle,
        string $expectedText,
        string $expectedCoverage,
        string $expectedMetaDescription
    ): void {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeText($expectedTitle);
        $testResponse->assertSeeText($expectedText);
        $testResponse->assertSeeText($expectedCoverage);
        $testResponse->assertSee($expectedMetaDescription);
    }
}
