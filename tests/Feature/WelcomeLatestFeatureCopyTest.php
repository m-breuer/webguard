<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeLatestFeatureCopyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string}>
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
                'Domain Expiration Checks',
                'send proactive renewal warnings',
                'Server Health Monitoring',
                'per-monitor thresholds before health reports are marked down',
            ],
            'german' => [
                'de',
                'Erwartete HTTP-Statusbereiche',
                'Definieren Sie akzeptierte Statuscodes oder Bereiche wie 200-299, 301 und 302',
                'Wöchentlicher Monitoring-Bericht',
                'wöchentliche E-Mail-Zusammenfassungen mit Uptime, Incidents, längster Downtime',
                'Domain-Ablaufprüfungen',
                'proaktive Verlängerungswarnungen',
                'Server-Zustand-Monitoring',
                'pro Monitor eigene Schwellen',
            ],
        ];
    }

    #[DataProvider('latestFeatureCopyProvider')]
    public function test_it_renders_latest_features_on_the_welcome_page(
        string $locale,
        string $expectedHttpStatusTitle,
        string $expectedHttpStatusText,
        string $expectedDigestTitle,
        string $expectedDigestText,
        string $expectedDomainTitle,
        string $expectedDomainText,
        string $expectedServerHealthTitle,
        string $expectedServerHealthText
    ): void {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

        $testResponse->assertOk();
        $testResponse->assertSeeText($expectedHttpStatusTitle);
        $testResponse->assertSeeText($expectedHttpStatusText);
        $testResponse->assertSeeText($expectedDigestTitle);
        $testResponse->assertSeeText($expectedDigestText);
        $testResponse->assertSeeText($expectedDomainTitle);
        $testResponse->assertSeeText($expectedDomainText);
        $testResponse->assertSeeText($expectedServerHealthTitle);
        $testResponse->assertSeeText($expectedServerHealthText);
    }

    public function test_documentation_covers_latest_features(): void
    {
        $readme = file_get_contents(base_path('README.md'));
        $features = file_get_contents(base_path('docs/features.md'));

        $this->assertIsString($readme);
        $this->assertIsString($features);
        $this->assertStringContainsString('docs/features.md', $readme);
        $this->assertStringContainsString('Expected HTTP status ranges', $features);
        $this->assertStringContainsString('200-299, 301, 302', $features);
        $this->assertStringContainsString('Weekly monitoring digest', $features);
        $this->assertStringContainsString('weekly uptime, incident, downtime, SSL, and domain expiry summaries', $features);
        $this->assertStringContainsString('receive proactive renewal warnings', $features);
        $this->assertStringContainsString('status changes, SSL expiry, and domain expiry', $features);
        $this->assertStringContainsString('Server health monitoring', $features);
        $this->assertStringContainsString('configurable per-monitor usage thresholds', $features);
    }
}
