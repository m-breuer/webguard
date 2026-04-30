<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FooterMarkupTest extends TestCase
{
    public function test_footer_renders_mobile_wrapping_layout_classes_and_public_links(): void
    {
        $testResponse = $this->get(route('welcome'));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('class="w-full sm:w-auto"');
        $testResponse->assertSeeHtml('flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:justify-end');
        $testResponse->assertSeeHtml(route('monitoring-locations'));
        $testResponse->assertSeeHtml(route('imprint'));
        $testResponse->assertSeeHtml(route('terms-of-use'));
        $testResponse->assertSeeHtml(route('gdpr'));
    }
}
