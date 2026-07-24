<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PublicIndexingTest extends TestCase
{
    public function test_marketing_and_legal_pages_are_not_served_by_core(): void
    {
        foreach (['/features', '/features/http-monitoring', '/monitoring-locations', '/imprint', '/terms-of-use', '/gdpr'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_robots_command_does_not_advertise_a_core_sitemap(): void
    {
        $robotsPath = public_path('robots.txt');
        $originalRobotsContent = file_get_contents($robotsPath);

        $this->assertIsString($originalRobotsContent);

        try {
            $this->artisan('robots:generate')->assertSuccessful();

            $robotsContent = file_get_contents($robotsPath);

            $this->assertIsString($robotsContent);
            $this->assertSame("User-agent: *\nAllow: /\n", $robotsContent);
            $this->assertStringNotContainsString('/sitemap.xml', $robotsContent);
        } finally {
            file_put_contents($robotsPath, $originalRobotsContent);
        }
    }
}
