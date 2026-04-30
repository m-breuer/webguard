<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class CiWorkflowRedisExtensionTest extends TestCase
{
    public function test_ci_test_job_installs_required_redis_extension(): void
    {
        $composerConfig = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('ext-redis', $composerConfig['require']);

        $workflowConfig = Yaml::parseFile(base_path('.github/workflows/ci.yml'));
        $testJobSteps = $workflowConfig['jobs']['test']['steps'] ?? [];
        $setupPhpStep = collect($testJobSteps)->firstWhere('name', 'Setup PHP');

        $this->assertIsArray($setupPhpStep);
        $this->assertIsString($setupPhpStep['with']['extensions'] ?? null);
        $this->assertStringContainsString('redis', $setupPhpStep['with']['extensions']);
    }
}
