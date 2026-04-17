<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeMonitoringIntervalCopyTest extends TestCase
{
    #[DataProvider('configuredIntervalCopyProvider')]
    public function test_it_renders_the_configured_monitoring_interval_copy_on_the_welcome_page(
        string $locale,
        int $interval,
        string $expectedCopy
    ): void {
        config(['monitoring.interval' => $interval]);

        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeText($expectedCopy);
    }

    #[DataProvider('perRequestIntervalCopyProvider')]
    public function test_it_updates_the_monitoring_interval_copy_for_each_request(
        string $locale,
        string $firstExpectedCopy,
        string $secondExpectedCopy
    ): void {
        config(['monitoring.interval' => 5]);

        $firstResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $firstResponse->assertOk();
        $firstResponse->assertSeeText($firstExpectedCopy);

        config(['monitoring.interval' => 1]);

        $secondResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $secondResponse->assertOk();
        $secondResponse->assertSeeText($secondExpectedCopy);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function configuredIntervalCopyProvider(): array
    {
        return [
            'english plural' => ['en', 5, '5 minutes'],
            'english singular' => ['en', 1, '1 minute'],
            'german plural' => ['de', 5, '5 Minuten'],
            'german singular' => ['de', 1, '1 Minute'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function perRequestIntervalCopyProvider(): array
    {
        return [
            'english request update' => ['en', '5 minutes', '1 minute'],
            'german request update' => ['de', '5 Minuten', '1 Minute'],
        ];
    }
}
