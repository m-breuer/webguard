<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationBrandingTest extends TestCase
{
    public function test_scribe_uses_a_publicly_deployed_logo_asset(): void
    {
        $logoPath = 'images/Logo-WebGuard.png';

        $this->assertSame($logoPath, config('scribe.logo'));
        $this->assertFileExists(public_path($logoPath));
    }
}
