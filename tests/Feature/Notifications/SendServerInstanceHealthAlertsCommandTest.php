<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Mail\ServerInstanceHealthAlertMail;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SendServerInstanceHealthAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();
        app()->setLocale((string) config('app.locale'));

        parent::tearDown();
    }

    public function test_sends_stale_alerts_to_verified_admins(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $unverifiedAdmin = User::factory()->unverified()->create(['role' => UserRole::ADMIN]);
        $member = User::factory()->create(['role' => UserRole::REGULAR]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-de-1',
            'ip_address' => '192.0.2.40',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(11),
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail) use ($admin, $serverInstance): bool {
            return $serverInstanceHealthAlertMail->hasTo($admin->email)
                && $serverInstanceHealthAlertMail->serverInstance->is($serverInstance)
                && $serverInstanceHealthAlertMail->healthStatus === 'stale';
        });
        Mail::assertNotSent(ServerInstanceHealthAlertMail::class, fn (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail): bool => $serverInstanceHealthAlertMail->hasTo($unverifiedAdmin->email));
        Mail::assertNotSent(ServerInstanceHealthAlertMail::class, fn (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail): bool => $serverInstanceHealthAlertMail->hasTo($member->email));

        $serverInstance->refresh();
        $this->assertSame('stale', $serverInstance->last_health_alert_status);
        $this->assertSame(Date::now()->toDateTimeString(), $serverInstance->last_health_alerted_at?->toDateTimeString());
    }

    public function test_does_not_repeat_same_stale_alert(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        User::factory()->create(['role' => UserRole::ADMIN]);
        ServerInstance::query()->create([
            'code' => 'scanner-us-1',
            'ip_address' => '192.0.2.41',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(15),
            'last_health_alert_status' => 'stale',
            'last_health_alerted_at' => Date::now()->subMinutes(4),
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertNothingSent();
    }

    public function test_does_not_update_alert_state_without_verified_admins(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        User::factory()->unverified()->create(['role' => UserRole::ADMIN]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-no-admin-1',
            'ip_address' => '192.0.2.46',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(15),
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertNothingSent();
        $this->assertNull($serverInstance->fresh()->last_health_alert_status);
        $this->assertNull($serverInstance->fresh()->last_health_alerted_at);
    }

    public function test_never_seen_alert_respects_grace_period(): void
    {
        config(['monitoring.instance_never_seen_alert_after_minutes' => 15]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-new-1',
            'ip_address' => '192.0.2.42',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => null,
        ]);
        $serverInstance->forceFill([
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ])->saveQuietly();
        $lateInstance = ServerInstance::query()->create([
            'code' => 'scanner-late-1',
            'ip_address' => '192.0.2.43',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => null,
        ]);
        $lateInstance->forceFill([
            'created_at' => Date::now()->subMinutes(16),
            'updated_at' => Date::now()->subMinutes(16),
        ])->saveQuietly();

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail) use ($admin, $lateInstance): bool {
            return $serverInstanceHealthAlertMail->hasTo($admin->email)
                && $serverInstanceHealthAlertMail->serverInstance->is($lateInstance)
                && $serverInstanceHealthAlertMail->healthStatus === 'never_seen';
        });
        Mail::assertNotSent(ServerInstanceHealthAlertMail::class, fn (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail): bool => $serverInstanceHealthAlertMail->serverInstance->is($serverInstance));

        $this->assertNull($serverInstance->fresh()->last_health_alert_status);
        $this->assertSame('never_seen', $lateInstance->fresh()->last_health_alert_status);
    }

    public function test_never_seen_alert_uses_minimum_one_minute_grace_period(): void
    {
        config(['monitoring.instance_never_seen_alert_after_minutes' => 0]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-minimum-grace-1',
            'ip_address' => '192.0.2.48',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => null,
        ]);
        $serverInstance->forceFill([
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ])->saveQuietly();

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail) use ($admin, $serverInstance): bool {
            return $serverInstanceHealthAlertMail->hasTo($admin->email)
                && $serverInstanceHealthAlertMail->serverInstance->is($serverInstance)
                && $serverInstanceHealthAlertMail->healthStatus === 'never_seen';
        });
    }

    public function test_does_not_repeat_same_never_seen_alert(): void
    {
        config(['monitoring.instance_never_seen_alert_after_minutes' => 15]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        User::factory()->create(['role' => UserRole::ADMIN]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-never-seen-1',
            'ip_address' => '192.0.2.47',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => null,
            'last_health_alert_status' => 'never_seen',
            'last_health_alerted_at' => Date::now()->subMinutes(4),
        ]);
        $serverInstance->forceFill([
            'created_at' => Date::now()->subMinutes(30),
            'updated_at' => Date::now()->subMinutes(30),
        ])->saveQuietly();

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertNothingSent();
        $this->assertSame('never_seen', $serverInstance->fresh()->last_health_alert_status);
        $this->assertSame(
            Date::now()->subMinutes(4)->toDateTimeString(),
            $serverInstance->fresh()->last_health_alerted_at?->toDateTimeString()
        );
    }

    public function test_sends_recovery_after_reported_instance_becomes_healthy(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-recovered-1',
            'ip_address' => '192.0.2.44',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(2),
            'last_health_alert_status' => 'stale',
            'last_health_alerted_at' => Date::now()->subHour(),
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $serverInstanceHealthAlertMail) use ($admin, $serverInstance): bool {
            return $serverInstanceHealthAlertMail->hasTo($admin->email)
                && $serverInstanceHealthAlertMail->serverInstance->is($serverInstance)
                && $serverInstanceHealthAlertMail->healthStatus === 'healthy';
        });

        $serverInstance->refresh();
        $this->assertSame('healthy', $serverInstance->last_health_alert_status);
        $this->assertSame(Date::now()->toDateTimeString(), $serverInstance->last_health_alerted_at?->toDateTimeString());
    }

    public function test_healthy_instance_without_previous_problem_does_not_alert(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        User::factory()->create(['role' => UserRole::ADMIN]);
        ServerInstance::query()->create([
            'code' => 'scanner-already-healthy-1',
            'ip_address' => '192.0.2.49',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinute(),
            'last_health_alert_status' => 'healthy',
            'last_health_alerted_at' => Date::now()->subHour(),
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertNothingSent();
    }

    public function test_blank_admin_email_is_skipped_without_blocking_alert_state_update(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->forceFill(['email' => ''])->saveQuietly();
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-blank-admin-email-1',
            'ip_address' => '192.0.2.50',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(15),
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertNothingSent();
        $this->assertSame('stale', $serverInstance->fresh()->last_health_alert_status);
    }

    public function test_mail_delivery_failures_are_logged_without_stopping_command(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $serverInstance = ServerInstance::query()->create([
            'code' => 'scanner-mail-failure-1',
            'ip_address' => '192.0.2.51',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => Date::now()->subMinutes(15),
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with($admin->email)
            ->andReturn(new class
            {
                public function send(mixed $mailable): void
                {
                    throw new RuntimeException('SMTP unavailable');
                }
            });
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send server instance health alert.', Mockery::on(function (array $context) use ($admin, $serverInstance): bool {
                return $context['server_instance_id'] === $serverInstance->id
                    && $context['server_instance_code'] === $serverInstance->code
                    && $context['health_status'] === 'stale'
                    && $context['admin_id'] === $admin->id
                    && $context['exception'] === 'SMTP unavailable';
            }));

        $this->assertSame(0, Artisan::call('notifications:send-server-instance-health-alerts'));
        $this->assertSame('stale', $serverInstance->fresh()->last_health_alert_status);
    }

    public function test_inactive_instances_do_not_alert(): void
    {
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        User::factory()->create(['role' => UserRole::ADMIN]);
        ServerInstance::query()->create([
            'code' => 'scanner-disabled-1',
            'ip_address' => '192.0.2.45',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => false,
            'last_seen_at' => null,
        ]);

        Artisan::call('notifications:send-server-instance-health-alerts');

        Mail::assertNothingSent();
    }

    public function test_server_instance_health_alert_mail_renders_alert_details(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        Date::setTestNow('2026-06-11 10:00:00');

        $admin = User::factory()->make([
            'name' => 'Admin User',
            'role' => UserRole::ADMIN,
        ]);
        $serverInstance = new ServerInstance([
            'code' => 'scanner-mail-1',
            'ip_address' => '192.0.2.47',
            'last_seen_at' => Date::now()->subMinutes(12),
        ]);

        $serverInstanceHealthAlertMail = new ServerInstanceHealthAlertMail($serverInstance, 'stale', $admin);
        $rendered = $serverInstanceHealthAlertMail->render();

        $this->assertSame('Server instance scanner-mail-1: UNREACHABLE', $serverInstanceHealthAlertMail->envelope()->subject);
        $this->assertSame([], $serverInstanceHealthAlertMail->attachments());
        $this->assertStringContainsString('Hello Admin User,', $rendered);
        $this->assertStringContainsString('The scanner instance &quot;scanner-mail-1&quot; currently has the status &quot;Unreachable&quot;.', $rendered);
        $this->assertStringContainsString('IP address: 192.0.2.47.', $rendered);
        $this->assertStringContainsString('considered unreachable after 10 minutes', $rendered);
        $this->assertStringContainsString('class="mail-button"', $rendered);
    }

    public function test_server_instance_health_alert_mail_renders_german_unreachable_copy(): void
    {
        config(['monitoring.instance_stale_after_minutes' => 10]);
        app()->setLocale('de');

        $admin = User::factory()->make([
            'name' => 'Marcel Breuer',
            'role' => UserRole::ADMIN,
        ]);
        $serverInstance = new ServerInstance([
            'code' => 'de-1',
            'ip_address' => '217.154.152.5',
            'last_seen_at' => Date::parse('2026-06-21 17:04:00'),
        ]);

        $serverInstanceHealthAlertMail = new ServerInstanceHealthAlertMail($serverInstance, 'stale', $admin);
        $rendered = $serverInstanceHealthAlertMail->render();

        $this->assertSame('Server-Instanz de-1: NICHT ERREICHBAR', $serverInstanceHealthAlertMail->envelope()->subject);
        $this->assertStringContainsString('Hallo Marcel Breuer,', $rendered);
        $this->assertStringContainsString('Die Scanner-Instanz &quot;de-1&quot; hat aktuell den Status &quot;Nicht erreichbar&quot;.', $rendered);
        $this->assertStringContainsString('IP-Adresse: 217.154.152.5.', $rendered);
        $this->assertStringContainsString('gelten nach 10 Minuten ohne erfolgreichen Bericht als nicht erreichbar', $rendered);
    }

    public function test_server_instance_health_alert_mail_renders_never_seen_copy(): void
    {
        config(['monitoring.instance_never_seen_alert_after_minutes' => 15]);

        $admin = User::factory()->make([
            'name' => 'Admin User',
            'role' => UserRole::ADMIN,
        ]);
        $serverInstance = new ServerInstance([
            'code' => 'scanner-new-mail-1',
            'ip_address' => '192.0.2.48',
            'last_seen_at' => null,
        ]);

        $serverInstanceHealthAlertMail = new ServerInstanceHealthAlertMail($serverInstance, 'never_seen', $admin);
        $rendered = $serverInstanceHealthAlertMail->render();

        $this->assertSame('Server instance scanner-new-mail-1: NO REPORT YET', $serverInstanceHealthAlertMail->envelope()->subject);
        $this->assertStringContainsString('The scanner instance &quot;scanner-new-mail-1&quot; currently has the status &quot;No report yet&quot;.', $rendered);
        $this->assertStringContainsString('No successful report has been received yet.', $rendered);
        $this->assertStringContainsString('after 15 minutes without a first successful report', $rendered);
    }

    public function test_server_instance_health_alert_mail_renders_recovery_copy(): void
    {
        $admin = User::factory()->make([
            'name' => 'Recovery Admin',
            'role' => UserRole::ADMIN,
        ]);
        $serverInstance = new ServerInstance([
            'code' => 'scanner-recovery-mail-1',
            'ip_address' => null,
            'last_seen_at' => null,
        ]);

        $rendered = (new ServerInstanceHealthAlertMail($serverInstance, 'healthy', $admin))->render();

        $this->assertStringContainsString('The scanner instance &quot;scanner-recovery-mail-1&quot; is reporting again and is now reachable.', $rendered);
        $this->assertStringContainsString('IP address: None.', $rendered);
        $this->assertStringContainsString('Last successful report: No report yet.', $rendered);
    }
}
