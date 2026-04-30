<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HeartbeatQueueDeploymentConfigTest extends TestCase
{
    public function test_docker_worker_processes_default_and_heartbeat_queues_by_default(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('HEARTBEAT_QUEUE: "heartbeat"', $composeConfiguration);
        $this->assertStringContainsString(
            'php artisan queue:work redis --queue=default,heartbeat',
            $composeConfiguration
        );
    }

    public function test_internal_database_and_redis_services_are_optional_for_external_deployments(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('DB_HOST: "${DB_HOST:-mysql}"', $composeConfiguration);
        $this->assertStringContainsString('REDIS_HOST: "${REDIS_HOST:-redis}"', $composeConfiguration);
        $this->assertStringContainsString('profiles:', $composeConfiguration);
        $this->assertStringContainsString('- internal-services', $composeConfiguration);
        $this->assertStringContainsString('required: false', $composeConfiguration);
        $this->assertStringNotContainsString('WEBGUARD_INSTANCE_API_KEY', $composeConfiguration);
        $this->assertStringNotContainsString('DB_ROOT_PASSWORD', $composeConfiguration);
    }
}
