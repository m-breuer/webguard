<?php

declare(strict_types=1);

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ViteManifestAssetTest extends TestCase
{
    public function test_blade_image_assets_are_registered_as_vite_inputs(): void
    {
        $viteConfig = file_get_contents(base_path('vite.config.js'));

        $this->assertIsString($viteConfig);

        foreach ($this->bladeImageAssets() as $assetPath) {
            $this->assertStringContainsString(
                "'{$assetPath}'",
                $viteConfig,
                sprintf('Blade asset "%s" must be listed as a Vite input so production builds include it in manifest.json.', $assetPath)
            );
        }
    }

    /**
     * @return list<string>
     */
    private function bladeImageAssets(): array
    {
        $assets = [];
        $views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        foreach ($views as $view) {
            if (! $view instanceof SplFileInfo) {
                continue;
            }
            if (! $view->isFile()) {
                continue;
            }
            if ($view->getExtension() !== 'php') {
                continue;
            }
            $contents = file_get_contents($view->getPathname());
            $this->assertIsString($contents);

            preg_match_all('/resources\\/images\\/[A-Za-z0-9_\\-.\\/]+/', $contents, $matches);

            foreach ($matches[0] as $assetPath) {
                $assets[$assetPath] = true;
            }
        }

        return array_values(array_keys($assets));
    }
}
