<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HeartbeatQueueDeploymentConfigTest extends TestCase
{
    public function test_nixpacks_supervisor_starts_a_dedicated_heartbeat_queue_worker_for_the_configured_queue_name(): void
    {
        $nixpacksConfiguration = file_get_contents(base_path('nixpacks.toml'));

        $this->assertIsString($nixpacksConfiguration);
        $this->assertStringContainsString('"worker-laravel-heartbeat.conf"', $nixpacksConfiguration);
        $this->assertStringContainsString(
            'php /app/artisan queue:work redis --queue=${HEARTBEAT_QUEUE:-heartbeat} --sleep=3 --tries=3 --max-time=3600',
            $nixpacksConfiguration
        );
    }

    public function test_nixpacks_build_does_not_mutate_laravel_runtime_directory_permissions(): void
    {
        $nixpacksConfiguration = file_get_contents(base_path('nixpacks.toml'));

        $this->assertIsString($nixpacksConfiguration);
        $this->assertStringNotContainsString('chmod -R 775 /app/storage /app/bootstrap/cache', $nixpacksConfiguration);
        $this->assertStringNotContainsString('chown -R www-data:www-data /app/storage /app/bootstrap/cache', $nixpacksConfiguration);
    }

    public function test_nixpacks_uses_deterministic_dependency_install_commands_for_deployments(): void
    {
        $nixpacksConfiguration = file_get_contents(base_path('nixpacks.toml'));

        $this->assertIsString($nixpacksConfiguration);
        $this->assertStringContainsString('nixPkgs = ["...", "python311Packages.supervisor"]', $nixpacksConfiguration);
        $this->assertStringContainsString(
            '"composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader"',
            $nixpacksConfiguration
        );
        $this->assertStringContainsString('"bun install --frozen-lockfile"', $nixpacksConfiguration);
        $this->assertStringNotContainsString('"bun i --no-save"', $nixpacksConfiguration);
    }

    public function test_nixpacks_build_leaves_frontend_build_to_the_default_plan(): void
    {
        $nixpacksConfiguration = file_get_contents(base_path('nixpacks.toml'));

        $this->assertIsString($nixpacksConfiguration);
        preg_match('/\[phases\.build\]\s+cmds = \[(.*?)\]/s', $nixpacksConfiguration, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringNotContainsString('bun install', $matches[1]);
        $this->assertStringNotContainsString('bun run build', $matches[1]);
    }
}
