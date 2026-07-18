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

    public function test_scheduler_container_uses_signal_aware_schedule_loop(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('php artisan schedule:run --verbose --no-interaction', $composeConfiguration);
        $this->assertStringContainsString('sleep 60 & wait %1', $composeConfiguration);
        $this->assertStringNotContainsString('php artisan schedule:work --verbose --no-interaction', $composeConfiguration);
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
                str_contains($interpolatedVariable, ':-') || str_contains($interpolatedVariable, ':?'),
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
        $this->assertStringContainsString('COPY --link docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
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

    public function test_obsolete_environment_variables_are_removed_from_environment_configuration(): void
    {
        foreach (['.env.example', '.env.testing', 'phpunit.xml'] as $environmentFile) {
            $environmentConfiguration = file_get_contents(base_path($environmentFile));

            $this->assertIsString($environmentConfiguration);

            foreach (['BCRYPT_ROUNDS', 'BROADCAST_CONNECTION', 'VITE_APP_NAME'] as $environmentVariable) {
                $this->assertStringNotContainsString($environmentVariable, $environmentConfiguration);
            }
        }
    }

    public function test_local_runtime_services_share_one_application_environment(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.override.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('x-local-app-environment: &local-app-environment', $composeConfiguration);
        $this->assertSame(3, mb_substr_count($composeConfiguration, '<<: *local-app-environment'));
        $this->assertSame(1, mb_substr_count($composeConfiguration, 'DB_HOST: "mysql"'));
        $this->assertSame(1, mb_substr_count($composeConfiguration, 'MAIL_HOST: "mailpit"'));
    }

    public function test_worker_image_does_not_depend_on_the_frontend_build_stage(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertIsString($dockerfile);
        $workerStageStart = mb_strpos($dockerfile, 'FROM serversideup/php:8.5-cli AS worker');

        $this->assertNotFalse($workerStageStart);
        $workerStage = mb_substr($dockerfile, $workerStageStart);
        $this->assertIsString($workerStage);
        $this->assertStringContainsString('COPY --link --from=app_build', $workerStage);
        $this->assertStringNotContainsString('frontend_build', $workerStage);
        $this->assertStringNotContainsString('bun install', $workerStage);
        $this->assertStringNotContainsString('install-php-extensions redis', $workerStage);
    }

    public function test_production_dockerfile_uses_dependency_cache_mounts(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertIsString($dockerfile);
        $this->assertStringContainsString('# syntax=docker/dockerfile:1.7', $dockerfile);
        $this->assertStringContainsString('COPY --from=composer:2', $dockerfile);
        $this->assertStringContainsString('--mount=type=cache,target=/tmp/composer-cache', $dockerfile);
        $this->assertStringContainsString('composer install --no-dev --no-autoloader', $dockerfile);
        $this->assertStringContainsString('FROM oven/bun:1 AS frontend_build', $dockerfile);
        $this->assertStringContainsString('--mount=type=cache,target=/tmp/bun-cache', $dockerfile);
        $this->assertStringContainsString('COPY --link resources resources', $dockerfile);
    }

    public function test_production_dockerfile_only_compiles_required_missing_php_extensions(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertIsString($dockerfile);
        $this->assertStringContainsString('--mount=type=cache,target=/var/cache/apt', $dockerfile);
        $this->assertStringContainsString('apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev', $dockerfile);
        $this->assertStringContainsString('docker-php-ext-configure gd --with-freetype --with-jpeg', $dockerfile);
        $this->assertStringContainsString('docker-php-ext-install -j"$(nproc)" gd', $dockerfile);
        $this->assertStringNotContainsString('install-php-extensions gd', $dockerfile);
        $this->assertStringNotContainsString('install-php-extensions bcmath gd intl pdo_mysql sockets zip redis', $dockerfile);
        $this->assertStringNotContainsString('install-php-extensions redis', $dockerfile);
        $this->assertStringContainsString('FROM base AS development', $dockerfile);
        $this->assertStringContainsString('FROM base AS ci', $dockerfile);
        $this->assertStringContainsString('install-php-extensions sockets', $dockerfile);
    }

    public function test_production_php_container_defaults_to_proxy_terminated_ssl(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('SSL_MODE: "${DOCKER_SSL_MODE:-off}"', $composeConfiguration);
        $this->assertStringContainsString('stop_signal: SIGTERM', $composeConfiguration);
        $this->assertStringContainsString('- "8080"', $composeConfiguration);
        $this->assertStringContainsString('- "8443"', $composeConfiguration);
    }

    public function test_production_php_container_can_generate_scribe_docs_on_startup_when_enabled(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $scribeEntrypoint = file_get_contents(base_path('docker/php/entrypoint.d/60-laravel-scribe-generate.sh'));

        $this->assertIsString($composeConfiguration);
        $this->assertIsString($dockerfile);
        $this->assertIsString($scribeEntrypoint);
        $this->assertStringContainsString(
            'AUTORUN_LARAVEL_SCRIBE_GENERATE: "${AUTORUN_LARAVEL_SCRIBE_GENERATE:-false}"',
            $composeConfiguration
        );
        $this->assertStringContainsString('COPY --link docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
        $this->assertStringContainsString('php "$APP_BASE_DIR/artisan" scribe:generate --force', $scribeEntrypoint);
    }

    public function test_production_php_container_can_generate_sitemap_on_startup_when_enabled(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $sitemapEntrypoint = file_get_contents(base_path('docker/php/entrypoint.d/55-laravel-sitemap-generate.sh'));

        $this->assertIsString($composeConfiguration);
        $this->assertIsString($dockerfile);
        $this->assertIsString($sitemapEntrypoint);
        $this->assertStringContainsString(
            'AUTORUN_LARAVEL_SITEMAP_GENERATE: "${AUTORUN_LARAVEL_SITEMAP_GENERATE:-false}"',
            $composeConfiguration
        );
        $this->assertStringContainsString('COPY --link docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
        $this->assertStringContainsString('php "$APP_BASE_DIR/artisan" sitemap:generate', $sitemapEntrypoint);
    }

    public function test_production_php_container_can_generate_robots_txt_on_startup_when_enabled(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $robotsEntrypoint = file_get_contents(base_path('docker/php/entrypoint.d/55-laravel-robots-generate.sh'));

        $this->assertIsString($composeConfiguration);
        $this->assertIsString($dockerfile);
        $this->assertIsString($robotsEntrypoint);
        $this->assertStringContainsString(
            'AUTORUN_LARAVEL_ROBOTS_GENERATE: "${AUTORUN_LARAVEL_ROBOTS_GENERATE:-true}"',
            $composeConfiguration
        );
        $this->assertStringContainsString('COPY --link docker/php/entrypoint.d/ /etc/entrypoint.d/', $dockerfile);
        $this->assertStringContainsString('php "$APP_BASE_DIR/artisan" robots:generate', $robotsEntrypoint);
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

    public function test_robots_entrypoint_skips_generation_unless_enabled(): void
    {
        $process = new Process([
            'env',
            '-i',
            'sh',
            base_path('docker/php/entrypoint.d/55-laravel-robots-generate.sh'),
        ]);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('', $process->getOutput());
    }

    public function test_robots_entrypoint_generates_robots_txt_when_enabled(): void
    {
        $appBaseDirectory = base_path('storage/framework/testing/robots-entrypoint-app');
        $argumentsPath = $appBaseDirectory . '/robots-arguments.txt';

        if (! is_dir($appBaseDirectory)) {
            mkdir($appBaseDirectory, 0777, true);
        }

        file_put_contents(
            $appBaseDirectory . '/artisan',
            <<<'PHP'
<?php
file_put_contents(__DIR__.'/robots-arguments.txt', implode(' ', array_slice($argv, 1)));
PHP
        );

        $process = new Process([
            'env',
            '-i',
            'AUTORUN_LARAVEL_ROBOTS_GENERATE=true',
            'APP_BASE_DIR=' . $appBaseDirectory,
            'PATH=' . getenv('PATH'),
            'sh',
            base_path('docker/php/entrypoint.d/55-laravel-robots-generate.sh'),
        ]);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('robots:generate', file_get_contents($argumentsPath));

        unlink($argumentsPath);
        unlink($appBaseDirectory . '/artisan');
        rmdir($appBaseDirectory);
    }

    public function test_production_php_container_exposes_http_port_for_coolify_proxy(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringContainsString('expose:', $composeConfiguration);
        $this->assertStringContainsString('- "8080"', $composeConfiguration);
        $this->assertStringNotContainsString('traefik.http.routers.', $composeConfiguration);
        $this->assertStringNotContainsString('Host(`${SERVICE_FQDN_PHP', $composeConfiguration);
        $this->assertStringNotContainsString('Host(`www.${SERVICE_FQDN_PHP', $composeConfiguration);
    }

    public function test_production_compose_lets_coolify_generate_proxy_labels(): void
    {
        $composeConfiguration = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($composeConfiguration);
        $this->assertStringNotContainsString('SERVICE_FQDN_PHP', $composeConfiguration);
        $this->assertStringNotContainsString('COOLIFY_RESOURCE_UUID', $composeConfiguration);
        $this->assertStringNotContainsString('tls.certresolver=letsencrypt', $composeConfiguration);
        $this->assertStringNotContainsString('loadbalancer.server.port=8080', $composeConfiguration);
        $this->assertStringNotContainsString('www-redirect.redirectregex', $composeConfiguration);
    }
}
