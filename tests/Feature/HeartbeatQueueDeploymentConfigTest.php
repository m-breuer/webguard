<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Process\Process;
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
        $this->assertStringContainsString('MAIL_USERNAME: "${SMTP_USERNAME:-null}"', $composeConfiguration);
        $this->assertStringNotContainsString('MAIL_USERNAME: "${MAIL_USERNAME:-null}"', $composeConfiguration);
        $this->assertStringContainsString('MAIL_FROM_NAME: "${MAIL_FROM_NAME:-WebGuard}"', $composeConfiguration);
    }

    public function test_docker_mail_username_input_uses_smtp_username_in_environment_example(): void
    {
        $environmentExample = file_get_contents(base_path('.env.example'));

        $this->assertIsString($environmentExample);
        $this->assertStringContainsString('SMTP_USERNAME=null', $environmentExample);
        $this->assertStringNotContainsString('MAIL_USERNAME=${MAIL_FROM_ADDRESS}', $environmentExample);
    }

    public function test_production_compose_uses_defaults_for_interpolated_environment_variables(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        preg_match_all('/\$\{([^}]+)}/', $composeConfiguration, $matches);

        foreach ($matches[1] as $interpolatedVariable) {
            $this->assertTrue(
                ctype_digit($interpolatedVariable)
                    || str_contains($interpolatedVariable, ':-')
                    || str_contains($interpolatedVariable, ':?'),
                sprintf('Compose variable "%s" must define a default value or be explicitly required.', $interpolatedVariable)
            );
        }
    }

    public function test_production_requires_app_key_before_serving_http_requests(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $appKeyEntrypoint = file_get_contents(base_path('docker/php/entrypoint.d/10-require-app-key.sh'));

        $this->assertIsString($composeConfiguration);
        $this->assertIsString($dockerfile);
        $this->assertIsString($appKeyEntrypoint);
        $this->assertStringContainsString(
            'APP_KEY: "${APP_KEY:?APP_KEY must be set to a persistent Laravel application key}"',
            $composeConfiguration
        );
        $this->assertStringContainsString('COPY docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
        $this->assertStringContainsString('APP_KEY must be set to a persistent Laravel application key in production.', $appKeyEntrypoint);
        $this->assertStringContainsString('php artisan key:generate --show', $appKeyEntrypoint);
    }

    public function test_production_container_requires_imprint_configuration_for_legal_pages(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);

        foreach ([
            'IMPRINT_OPERATOR_NAME',
            'IMPRINT_ADDRESS_STREET',
            'IMPRINT_ADDRESS_POSTAL_CODE',
            'IMPRINT_ADDRESS_CITY',
            'IMPRINT_ADDRESS_COUNTRY',
            'IMPRINT_CONTACT_EMAIL',
            'IMPRINT_CONTACT_PHONE',
        ] as $environmentVariable) {
            $this->assertStringContainsString(
                "{$environmentVariable}: \"\${{$environmentVariable}:?{$environmentVariable} must be set for legal pages}\"",
                $composeConfiguration
            );
        }
    }

    public function test_app_key_entrypoint_rejects_missing_production_key(): void
    {
        $process = new Process([
            'env',
            '-i',
            'APP_ENV=production',
            'sh',
            base_path('docker/php/entrypoint.d/10-require-app-key.sh'),
        ]);

        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString(
            'APP_KEY must be set to a persistent Laravel application key in production.',
            $process->getErrorOutput()
        );
    }

    public function test_app_key_entrypoint_allows_keyed_production_and_non_production_environments(): void
    {
        $productionProcess = new Process([
            'env',
            '-i',
            'APP_ENV=production',
            'APP_KEY=base64:test-key',
            'sh',
            base_path('docker/php/entrypoint.d/10-require-app-key.sh'),
        ]);
        $localProcess = new Process([
            'env',
            '-i',
            'APP_ENV=local',
            'sh',
            base_path('docker/php/entrypoint.d/10-require-app-key.sh'),
        ]);

        $productionProcess->run();
        $localProcess->run();

        $this->assertSame(0, $productionProcess->getExitCode(), $productionProcess->getErrorOutput());
        $this->assertSame(0, $localProcess->getExitCode(), $localProcess->getErrorOutput());
    }

    public function test_production_app_url_is_derived_from_coolify_service_url(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('APP_URL: "${SERVICE_URL_PHP:-https://webguard.example.com}"', $composeConfiguration);
        $this->assertStringNotContainsString('APP_URL: "${APP_URL:-https://webguard.example.com}"', $composeConfiguration);
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

    public function test_production_php_container_defaults_to_proxy_terminated_ssl(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('SSL_MODE: "${DOCKER_SSL_MODE:-off}"', $composeConfiguration);
        $this->assertStringContainsString('- "8080"', $composeConfiguration);
        $this->assertStringContainsString('- "8443"', $composeConfiguration);
    }

    public function test_production_php_container_generates_scribe_docs_on_startup(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $scribeEntrypoint = file_get_contents(base_path('docker/php/entrypoint.d/60-laravel-scribe-generate.sh'));

        $this->assertIsString($composeConfiguration);
        $this->assertIsString($dockerfile);
        $this->assertIsString($scribeEntrypoint);
        $this->assertStringContainsString('AUTORUN_LARAVEL_SCRIBE_GENERATE: "true"', $composeConfiguration);
        $this->assertStringContainsString('COPY docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
        $this->assertStringContainsString('php "$APP_BASE_DIR/artisan" scribe:generate --force', $scribeEntrypoint);
    }

    public function test_production_php_container_generates_sitemap_on_startup(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $sitemapEntrypoint = file_get_contents(base_path('docker/php/entrypoint.d/55-laravel-sitemap-generate.sh'));

        $this->assertIsString($composeConfiguration);
        $this->assertIsString($dockerfile);
        $this->assertIsString($sitemapEntrypoint);
        $this->assertStringContainsString('AUTORUN_LARAVEL_SITEMAP_GENERATE: "true"', $composeConfiguration);
        $this->assertStringContainsString('COPY docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
        $this->assertStringContainsString('php "$APP_BASE_DIR/artisan" sitemap:generate', $sitemapEntrypoint);
    }

    public function test_sitemap_entrypoint_skips_generation_unless_enabled(): void
    {
        $process = new Process([
            'env',
            '-i',
            'sh',
            base_path('docker/php/entrypoint.d/55-laravel-sitemap-generate.sh'),
        ]);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('', $process->getOutput());
    }

    public function test_sitemap_entrypoint_requires_artisan_when_generation_is_enabled(): void
    {
        $process = new Process([
            'env',
            '-i',
            'AUTORUN_LARAVEL_SITEMAP_GENERATE=true',
            'APP_BASE_DIR=' . base_path('storage/framework/testing/missing-sitemap-app'),
            'sh',
            base_path('docker/php/entrypoint.d/55-laravel-sitemap-generate.sh'),
        ]);

        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString(
            'Artisan file not found in ' . base_path('storage/framework/testing/missing-sitemap-app'),
            $process->getOutput()
        );
    }

    public function test_sitemap_entrypoint_generates_sitemap_when_enabled(): void
    {
        $appBaseDirectory = base_path('storage/framework/testing/sitemap-entrypoint-app');
        $argumentsPath = $appBaseDirectory . '/sitemap-arguments.txt';

        if (! is_dir($appBaseDirectory)) {
            mkdir($appBaseDirectory, 0777, true);
        }

        file_put_contents(
            $appBaseDirectory . '/artisan',
            <<<'PHP'
<?php
file_put_contents(__DIR__.'/sitemap-arguments.txt', implode(' ', array_slice($argv, 1)));
PHP
        );

        $process = new Process([
            'env',
            '-i',
            'AUTORUN_LARAVEL_SITEMAP_GENERATE=true',
            'APP_BASE_DIR=' . $appBaseDirectory,
            'PATH=' . getenv('PATH'),
            'sh',
            base_path('docker/php/entrypoint.d/55-laravel-sitemap-generate.sh'),
        ]);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('sitemap:generate', file_get_contents($argumentsPath));

        unlink($argumentsPath);
        unlink($appBaseDirectory . '/artisan');
        rmdir($appBaseDirectory);
    }

    public function test_production_php_container_declares_coolify_traefik_labels(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('traefik.enable=true', $composeConfiguration);
        $this->assertStringContainsString('traefik.docker.network=${WEBGUARD_NETWORK:-webguard-network}', $composeConfiguration);
        $this->assertStringContainsString('Host(`${SERVICE_FQDN_PHP:-webguard.example.com}`)', $composeConfiguration);
        $this->assertStringContainsString('entrypoints=https', $composeConfiguration);
        $this->assertStringContainsString('tls.certresolver=letsencrypt', $composeConfiguration);
        $this->assertStringContainsString('loadbalancer.server.port=8080', $composeConfiguration);
    }

    public function test_production_php_container_redirects_www_host_to_non_www(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString(
            'traefik.http.middlewares.webguard-${COOLIFY_RESOURCE_UUID:-local}-www-redirect.redirectregex.regex=^https?://www\.(.*)',
            $composeConfiguration
        );
        $this->assertStringContainsString(
            'traefik.http.middlewares.webguard-${COOLIFY_RESOURCE_UUID:-local}-www-redirect.redirectregex.replacement=https://$${1}',
            $composeConfiguration
        );
        $this->assertStringContainsString(
            'traefik.http.middlewares.webguard-${COOLIFY_RESOURCE_UUID:-local}-www-redirect.redirectregex.permanent=true',
            $composeConfiguration
        );
        $this->assertStringContainsString(
            'traefik.http.routers.webguard-${COOLIFY_RESOURCE_UUID:-local}-www-http.rule=Host(`www.${SERVICE_FQDN_PHP:-webguard.example.com}`) && PathPrefix(`/`)',
            $composeConfiguration
        );
        $this->assertStringContainsString(
            'traefik.http.routers.webguard-${COOLIFY_RESOURCE_UUID:-local}-www-https.rule=Host(`www.${SERVICE_FQDN_PHP:-webguard.example.com}`) && PathPrefix(`/`)',
            $composeConfiguration
        );
        $this->assertStringContainsString(
            'traefik.http.routers.webguard-${COOLIFY_RESOURCE_UUID:-local}-www-https.middlewares=webguard-${COOLIFY_RESOURCE_UUID:-local}-www-redirect',
            $composeConfiguration
        );
    }
}
