<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportedLanguage;
use App\Http\Controllers\PublicFeatureController;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicFeaturePagesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function featureSlugProvider(): array
    {
        return collect(PublicFeatureController::slugs())
            ->mapWithKeys(fn (string $slug): array => [$slug => [$slug]])
            ->all();
    }

    #[DataProvider('featureSlugProvider')]
    public function test_public_feature_detail_pages_are_available(string $slug): void
    {
        $feature = PublicFeatureController::features()[$slug];

        $testResponse = $this->get(route('public-features.show', $slug));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('public_features.features.' . $feature['key'] . '.title'));
        $testResponse->assertSeeText(__('public_features.show.how_it_helps'));
        $testResponse->assertSeeHtml(sprintf('<link rel="canonical" href="%s">', route('public-features.show', $slug)));
    }

    public function test_feature_overview_links_all_public_feature_pages_and_api_docs(): void
    {
        $testResponse = $this->get(route('public-features.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml(route('scribe'));

        foreach (PublicFeatureController::slugs() as $slug) {
            $testResponse->assertSeeHtml(route('public-features.show', $slug));
        }
    }

    public function test_monitoring_locations_feature_slug_redirects_to_public_locations_page(): void
    {
        $testResponse = $this->get(route('public-features.show', 'monitoring-locations'));

        $testResponse->assertRedirect(route('monitoring-locations'));
    }

    public function test_unknown_public_feature_slug_returns_not_found(): void
    {
        $testResponse = $this->get(route('public-features.show', 'unknown-feature'));

        $testResponse->assertNotFound();
    }

    public function test_generated_scribe_api_reference_is_publicly_available(): void
    {
        $testResponse = $this->get(route('scribe'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('WebGuard API Reference');
        $testResponse->assertSeeText('Server Health');
    }

    public function test_welcome_page_links_public_feature_pages_and_scribe_docs(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml(route('public-features.index'));
        $testResponse->assertSeeHtml(route('scribe'));
        $testResponse->assertSeeHtml(route('public-features.show', 'public-labels'));
        $testResponse->assertSeeText('Public Labels');
        $testResponse->assertSeeText('Status Badges and Embeddable Status Widget');
    }

    public function test_public_feature_pages_render_in_german(): void
    {
        $testResponse = $this->withCookie(SupportedLanguage::cookieName(), 'de')
            ->get(route('public-features.show', 'api'));

        $testResponse->assertOk();
        $testResponse->assertSeeText('REST API und Integrationen');
        $testResponse->assertSeeText('Öffentliche API-Referenz');
        $testResponse->assertSeeHtml(route('scribe'));
    }
}
