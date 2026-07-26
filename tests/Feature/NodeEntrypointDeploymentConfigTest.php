<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class NodeEntrypointDeploymentConfigTest extends TestCase
{
    public function test_dependency_recovery_preserves_the_mounted_node_modules_directory(): void
    {
        $entrypoint = file_get_contents(base_path('docker/node/entrypoint.sh'));

        $this->assertIsString($entrypoint);
        $this->assertStringContainsString(
            'find node_modules -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +',
            $entrypoint
        );
        $this->assertStringNotContainsString('rm -rf node_modules', $entrypoint);
    }
}
