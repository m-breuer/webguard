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

    public function test_ci_jobs_cache_composer_downloads_instead_of_vendor_directory(): void
    {
        $workflowConfig = Yaml::parseFile(base_path('.github/workflows/ci.yml'));

        foreach (['auto-fixes', 'test'] as $jobName) {
            $cacheStep = collect($workflowConfig['jobs'][$jobName]['steps'] ?? [])
                ->firstWhere('name', 'Cache Composer dependencies');

            $this->assertIsArray($cacheStep, "Missing Composer cache step for {$jobName}.");
            $this->assertSame('~/.cache/composer/files', $cacheStep['with']['path'] ?? null);
            $this->assertNotSame('vendor', $cacheStep['with']['path'] ?? null);
        }
    }

    public function test_ci_runs_phpstan_and_pest_coverage(): void
    {
        $composerConfig = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('^3.9', $composerConfig['require-dev']['larastan/larastan'] ?? null);
        $this->assertSame('^2.2', $composerConfig['require-dev']['phpstan/phpstan'] ?? null);
        $this->assertSame(['./vendor/bin/phpstan analyse --ansi --memory-limit=1G'], $composerConfig['scripts']['analyse'] ?? null);
        $this->assertSame(['./vendor/bin/pest --coverage --min=0'], $composerConfig['scripts']['test:coverage'] ?? null);
        $this->assertFileExists(base_path('phpstan.neon'));

        $workflowConfig = Yaml::parseFile(base_path('.github/workflows/ci.yml'));
        $testJobSteps = collect($workflowConfig['jobs']['test']['steps'] ?? []);
        $phpstanStep = $testJobSteps->firstWhere('name', 'Run PHPStan');
        $coverageStep = $testJobSteps->firstWhere('name', 'Run tests with coverage');

        $this->assertIsArray($phpstanStep);
        $this->assertSame('composer analyse', $phpstanStep['run'] ?? null);

        $this->assertIsArray($coverageStep);
        $this->assertStringContainsString('composer test:coverage', $coverageStep['run'] ?? '');
        $this->assertStringContainsString('--parallel', $coverageStep['run'] ?? '');
        $this->assertSame('sqlite', $coverageStep['env']['DB_CONNECTION'] ?? null);
        $this->assertSame(':memory:', $coverageStep['env']['DB_DATABASE'] ?? null);
        $this->assertSame('coverage', $coverageStep['env']['XDEBUG_MODE'] ?? null);
    }

    public function test_ci_verifies_the_generated_external_openapi_contract(): void
    {
        $workflowConfig = Yaml::parseFile(base_path('.github/workflows/ci.yml'));
        $testJobSteps = collect($workflowConfig['jobs']['test']['steps'] ?? []);
        $openApiStep = $testJobSteps->firstWhere('name', 'Verify external OpenAPI contract is current');

        $this->assertIsArray($openApiStep);
        $this->assertStringContainsString('php artisan scribe:generate --no-interaction', $openApiStep['run'] ?? '');
        $this->assertStringContainsString(
            'git diff --exit-code -- storage/app/private/scribe/openapi.yaml',
            $openApiStep['run'] ?? '',
        );
        $this->assertSame('sqlite', $openApiStep['env']['DB_CONNECTION'] ?? null);
        $this->assertSame(':memory:', $openApiStep['env']['DB_DATABASE'] ?? null);
    }

    public function test_captcha_uses_intervention_image_three_until_package_supports_v4(): void
    {
        $composerConfig = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);
        $composerLock = json_decode((string) file_get_contents(base_path('composer.lock')), true, 512, JSON_THROW_ON_ERROR);
        $packages = collect($composerLock['packages'] ?? [])->keyBy('name');

        $this->assertSame('^3.11', $composerConfig['require']['intervention/image'] ?? null);
        $this->assertTrue($packages->has('intervention/image'));
        $interventionImageVersion = mb_ltrim((string) $packages->get('intervention/image')['version'], 'v');

        $this->assertTrue(
            version_compare($interventionImageVersion, '4.0.0', '<'),
            'mews/captcha currently calls Intervention Image v3 APIs such as ImageManager::create().'
        );
        $this->assertTrue($packages->has('mews/captcha'));
    }

    public function test_weekly_dependency_update_caches_composer_downloads_instead_of_vendor_directory(): void
    {
        $workflowConfig = Yaml::parseFile(base_path('.github/workflows/weekly-dependency-update.yml'));
        $cacheStep = collect($workflowConfig['jobs']['update-dependencies']['steps'] ?? [])
            ->firstWhere('name', 'Cache Composer dependencies');

        $this->assertIsArray($cacheStep);
        $this->assertSame('~/.cache/composer/files', $cacheStep['with']['path'] ?? null);
        $this->assertNotSame('vendor', $cacheStep['with']['path'] ?? null);
    }
}
