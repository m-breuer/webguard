<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use App\Support\HttpStatusCodeRanges;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    public function test_registration_creates_a_redacted_user_audit_log(): void
    {
        Package::factory()->create([
            'price' => 0,
            'is_selectable' => true,
        ]);

        $testResponse = $this->post('/register', [
            'name' => 'Audit Tester',
            'email' => 'audit-registration@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
            'captcha' => $this->validCaptchaValue(),
        ]);

        $testResponse->assertRedirect(route('monitorings.index', absolute: false));

        $model = User::query()->where('email', 'audit-registration@example.test')->firstOrFail();
        $activity = Activity::query()
            ->where('log_name', 'user')
            ->where('event', 'created')
            ->where('subject_type', User::class)
            ->where('subject_id', $model->id)
            ->firstOrFail();

        $changes = $activity->attribute_changes->toArray();

        $this->assertSame('user_created', $activity->description);
        $this->assertSame('Audit Tester', data_get($changes, 'attributes.name'));
        $this->assertSame('audit-registration@example.test', data_get($changes, 'attributes.email'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.password'));
        $this->assertStringNotContainsString('Password123!', json_encode($changes, JSON_THROW_ON_ERROR));
    }

    public function test_profile_update_logs_changes_without_notification_secrets(): void
    {
        Package::factory()->create();
        $user = User::factory()->create([
            'theme' => 'system',
            'notification_channels_hint_seen_at' => now(),
        ]);
        Activity::query()->delete();

        $testResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Changed Audit User',
            'email' => $user->email,
            'theme' => 'dark',
            'monitoring_digest_enabled' => '1',
            'monitoring_digest_frequency' => 'monthly',
            'unread_notifications_reminder_enabled' => '1',
            'unread_notifications_reminder_frequency' => 'weekly',
            'notification_channels' => [
                'slack' => [
                    'enabled' => '1',
                    'webhook_url' => 'https://hooks.slack.com/services/raw-secret',
                ],
                'telegram' => [
                    'enabled' => '1',
                    'bot_token' => '12345:raw-telegram-secret',
                    'chat_id' => '-1001234567',
                ],
                'discord' => [
                    'enabled' => '0',
                    'webhook_url' => 'https://discord.com/api/webhooks/raw-secret',
                ],
                'webhook' => [
                    'enabled' => '1',
                    'url' => 'https://example.com/raw-webhook-secret',
                ],
            ],
        ]);

        $testResponse->assertRedirect(route('profile.edit'));

        $activity = Activity::query()
            ->where('log_name', 'user')
            ->where('event', 'updated')
            ->where('subject_id', $user->id)
            ->firstOrFail();
        $changes = $activity->attribute_changes->toArray();

        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame('Changed Audit User', data_get($changes, 'attributes.name'));
        $this->assertSame('dark', data_get($changes, 'attributes.theme'));
        $this->assertTrue(data_get($changes, 'attributes.notification_channels.slack.enabled'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.notification_channels.slack.webhook_url'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.notification_channels.telegram.bot_token'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.notification_channels.telegram.chat_id'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.notification_channels.webhook.url'));

        $encodedChanges = json_encode($changes, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('raw-secret', $encodedChanges);
        $this->assertStringNotContainsString('raw-telegram-secret', $encodedChanges);
        $this->assertStringNotContainsString('raw-webhook-secret', $encodedChanges);
    }

    public function test_monitoring_create_and_update_are_logged_with_redacted_secrets(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = $this->activeServerInstance();
        Activity::query()->delete();

        $payload = $this->httpMonitoringPayload($serverInstance, [
            'name' => 'Sensitive HTTP Monitor',
            'http_headers' => '{"Authorization":"Bearer raw-header-token","X-Trace":"abc-123"}',
            'http_body' => '{"password":"raw-body-secret"}',
            'auth_username' => 'audit-user',
            'auth_password' => 'raw-basic-password',
        ]);

        $testResponse = $this->actingAs($user)->post(route('monitorings.store'), $payload);
        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Sensitive HTTP Monitor')->firstOrFail();
        $createActivity = Activity::query()
            ->where('log_name', 'monitoring')
            ->where('event', 'created')
            ->where('subject_id', $monitoring->id)
            ->firstOrFail();
        $createChanges = $createActivity->attribute_changes->toArray();

        $this->assertSame($user->id, $createActivity->causer_id);
        $this->assertSame('Sensitive HTTP Monitor', data_get($createChanges, 'attributes.name'));
        $this->assertSame('[redacted]', data_get($createChanges, 'attributes.http_headers.Authorization'));
        $this->assertSame('abc-123', data_get($createChanges, 'attributes.http_headers.X-Trace'));
        $this->assertSame('[redacted]', data_get($createChanges, 'attributes.http_body'));
        $this->assertSame('[redacted]', data_get($createChanges, 'attributes.auth_password'));

        $updatePayload = $this->httpMonitoringPayload($serverInstance, [
            'name' => 'Renamed Sensitive HTTP Monitor',
            'status' => $monitoring->status->value,
            'preferred_location' => $monitoring->preferred_location,
            'http_headers' => '{"Authorization":"Bearer fresh-header-token","X-Trace":"changed"}',
            'http_body' => '{"secret":"fresh-body-secret"}',
            'auth_password' => 'fresh-basic-password',
        ]);
        unset($updatePayload['target']);

        $updateResponse = $this->actingAs($user)->patch(route('monitorings.update', $monitoring), $updatePayload);
        $updateResponse->assertRedirect(route('monitorings.show', $monitoring));

        $updateActivity = Activity::query()
            ->where('log_name', 'monitoring')
            ->where('event', 'updated')
            ->where('subject_id', $monitoring->id)
            ->latest()
            ->firstOrFail();
        $updateChanges = $updateActivity->attribute_changes->toArray();
        $encodedChanges = json_encode($updateChanges, JSON_THROW_ON_ERROR);

        $this->assertSame('Renamed Sensitive HTTP Monitor', data_get($updateChanges, 'attributes.name'));
        $this->assertSame('Sensitive HTTP Monitor', data_get($updateChanges, 'old.name'));
        $this->assertStringNotContainsString('fresh-header-token', $encodedChanges);
        $this->assertStringNotContainsString('fresh-body-secret', $encodedChanges);
        $this->assertStringNotContainsString('fresh-basic-password', $encodedChanges);
    }

    public function test_heartbeat_monitoring_audit_log_redacts_ping_url_and_token(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $serverInstance = $this->activeServerInstance();
        Activity::query()->delete();

        $testResponse = $this->actingAs($user)->post(route('monitorings.store'), [
            'name' => 'Sensitive Heartbeat Monitor',
            'type' => MonitoringType::HEARTBEAT->value,
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'heartbeat_interval_minutes' => 60,
            'heartbeat_grace_minutes' => 10,
            'preferred_location' => $serverInstance->code,
            'ssl_expiry_warning_days' => 7,
        ]);

        $testResponse->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::query()->where('name', 'Sensitive Heartbeat Monitor')->firstOrFail();
        $activity = Activity::query()
            ->where('log_name', 'monitoring')
            ->where('event', 'created')
            ->where('subject_id', $monitoring->id)
            ->firstOrFail();
        $changes = $activity->attribute_changes->toArray();
        $encodedChanges = json_encode($changes, JSON_THROW_ON_ERROR);

        $this->assertSame('[redacted]', data_get($changes, 'attributes.target'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.heartbeat_token'));
        $this->assertStringNotContainsString((string) $monitoring->target, $encodedChanges);
        $this->assertStringNotContainsString((string) $monitoring->heartbeat_token, $encodedChanges);
    }

    public function test_manual_user_and_monitoring_actions_are_logged(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $this->activeServerInstance()->code,
        ]);
        Activity::query()->delete();

        $testResponse = $this->actingAs($user)->post(route('profile.api-generate-token'));
        $testResponse->assertRedirect(route('profile.edit', ['#api-token']));

        $resetResponse = $this->actingAs($user)->delete(route('monitorings.destroyResults', $monitoring));
        $resetResponse->assertRedirect(route('monitorings.show', $monitoring));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user',
            'event' => 'api_token_generated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'monitoring',
            'event' => 'results_deleted',
            'subject_type' => Monitoring::class,
            'subject_id' => $monitoring->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    private function activeServerInstance(): ServerInstance
    {
        return ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function httpMonitoringPayload(ServerInstance $serverInstance, array $overrides = []): array
    {
        return array_merge([
            'name' => 'HTTP Monitoring',
            'type' => MonitoringType::HTTP->value,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE->value,
            'timeout' => 5,
            'http_method' => 'get',
            'expected_http_statuses' => HttpStatusCodeRanges::DEFAULT,
            'http_headers' => null,
            'http_body' => null,
            'auth_username' => null,
            'auth_password' => null,
            'preferred_location' => $serverInstance->code,
            'ssl_expiry_warning_days' => 7,
        ], $overrides);
    }
}
