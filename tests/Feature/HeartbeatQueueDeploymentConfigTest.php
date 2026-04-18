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
}
