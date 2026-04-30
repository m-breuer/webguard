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
        $this->assertStringContainsString('REDIS_USERNAME: "${REDIS_USERNAME:-null}"', $composeConfiguration);
        $this->assertStringContainsString('profiles:', $composeConfiguration);
        $this->assertStringContainsString('- internal-services', $composeConfiguration);
        $this->assertStringContainsString('required: false', $composeConfiguration);
        $this->assertStringContainsString('name: "${WEBGUARD_NETWORK:-webguard-network}"', $composeConfiguration);
        $this->assertStringContainsString('external: true', $composeConfiguration);
        $this->assertStringNotContainsString('WEBGUARD_INSTANCE_API_KEY', $composeConfiguration);
        $this->assertStringNotContainsString('DB_ROOT_PASSWORD', $composeConfiguration);
    }

    public function test_production_mail_encryption_is_available_to_the_container(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('MAIL_ENCRYPTION: "${MAIL_ENCRYPTION:-tls}"', $composeConfiguration);
        $this->assertStringContainsString('MAIL_FROM_NAME: "${MAIL_FROM_NAME:-WebGuard}"', $composeConfiguration);
    }

    public function test_production_compose_uses_defaults_for_interpolated_environment_variables(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        preg_match_all('/\$\{([^}]+)}/', $composeConfiguration, $matches);

        foreach ($matches[1] as $interpolatedVariable) {
            $this->assertStringContainsString(
                ':-',
                $interpolatedVariable,
                sprintf('Compose variable "%s" must define a default value.', $interpolatedVariable)
            );
        }
    }

    public function test_env_example_uses_literal_values_instead_of_nested_interpolation(): void
    {
        $environmentExample = file_get_contents(base_path('.env.example'));

        $this->assertIsString($environmentExample);
        $this->assertStringNotContainsString('${', $environmentExample);
        $this->assertStringNotContainsString('{$', $environmentExample);
    }

    public function test_worker_image_does_not_depend_on_the_frontend_build_stage(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertIsString($dockerfile);
        $workerStageStart = mb_strpos($dockerfile, 'FROM serversideup/php:8.5-cli AS worker');

        $this->assertNotFalse($workerStageStart);
        $workerStage = mb_substr($dockerfile, $workerStageStart);
        $this->assertIsString($workerStage);
        $this->assertStringContainsString('COPY --from=app_build', $workerStage);
        $this->assertStringNotContainsString('frontend_build', $workerStage);
        $this->assertStringNotContainsString('bun install', $workerStage);
    }

    public function test_production_php_container_enables_automatic_self_signed_ssl(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('SSL_MODE: "mixed"', $composeConfiguration);
        $this->assertStringContainsString('- "8080"', $composeConfiguration);
        $this->assertStringContainsString('- "8443"', $composeConfiguration);
    }
}
