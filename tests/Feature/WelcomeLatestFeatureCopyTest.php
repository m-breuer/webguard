<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeLatestFeatureCopyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function latestFeatureCopyProvider(): array
    {
        return [
            'english' => [
                'en',
                'Expected HTTP Status Ranges',
                'Define accepted status codes or ranges such as 200-299, 301, and 302',
                'Weekly Monitoring Digest',
                'weekly email summaries with uptime, incidents, longest downtime',
            ],
            'german' => [
                'de',
                'Erwartete HTTP-Statusbereiche',
                'Definieren Sie akzeptierte Statuscodes oder Bereiche wie 200-299, 301 und 302',
                'Wöchentlicher Monitoring-Bericht',
                'wöchentliche E-Mail-Zusammenfassungen mit Uptime, Incidents, längster Downtime',
            ],
        ];
    }

    #[DataProvider('latestFeatureCopyProvider')]
    public function test_it_renders_latest_features_on_the_welcome_page(
        string $locale,
        string $expectedHttpStatusTitle,
        string $expectedHttpStatusText,
        string $expectedDigestTitle,
        string $expectedDigestText
    ): void {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeText($expectedHttpStatusTitle);
        $testResponse->assertSeeText($expectedHttpStatusText);
        $testResponse->assertSeeText($expectedDigestTitle);
        $testResponse->assertSeeText($expectedDigestText);
    }

    public function test_readme_documents_latest_features(): void
    {
        $readme = file_get_contents(base_path('README.md'));

        $this->assertIsString($readme);
        $this->assertStringContainsString('Expected HTTP Status Ranges', $readme);
        $this->assertStringContainsString('200-299, 301, 302', $readme);
        $this->assertStringContainsString('Weekly Monitoring Digest', $readme);
        $this->assertStringContainsString('weekly uptime, incident, downtime, SSL, and domain expiry summaries', $readme);
    }
}
