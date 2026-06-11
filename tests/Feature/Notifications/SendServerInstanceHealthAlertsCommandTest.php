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
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendServerInstanceHealthAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

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

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $mail) use ($admin, $serverInstance): bool {
            return $mail->hasTo($admin->email)
                && $mail->serverInstance->is($serverInstance)
                && $mail->healthStatus === 'stale';
        });
        Mail::assertNotSent(ServerInstanceHealthAlertMail::class, fn (ServerInstanceHealthAlertMail $mail): bool => $mail->hasTo($unverifiedAdmin->email));
        Mail::assertNotSent(ServerInstanceHealthAlertMail::class, fn (ServerInstanceHealthAlertMail $mail): bool => $mail->hasTo($member->email));

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

    public function test_never_seen_alert_respects_grace_period(): void
    {
        config(['monitoring.instance_never_seen_alert_after_minutes' => 15]);
        Date::setTestNow('2026-06-11 10:00:00');
        Mail::fake();

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $newInstance = ServerInstance::query()->create([
            'code' => 'scanner-new-1',
            'ip_address' => '192.0.2.42',
            'api_key_hash' => 'valid-instance-key',
            'is_active' => true,
            'last_seen_at' => null,
        ]);
        $newInstance->forceFill([
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

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $mail) use ($admin, $lateInstance): bool {
            return $mail->hasTo($admin->email)
                && $mail->serverInstance->is($lateInstance)
                && $mail->healthStatus === 'never_seen';
        });
        Mail::assertNotSent(ServerInstanceHealthAlertMail::class, fn (ServerInstanceHealthAlertMail $mail): bool => $mail->serverInstance->is($newInstance));

        $this->assertNull($newInstance->fresh()->last_health_alert_status);
        $this->assertSame('never_seen', $lateInstance->fresh()->last_health_alert_status);
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

        Mail::assertSent(ServerInstanceHealthAlertMail::class, function (ServerInstanceHealthAlertMail $mail) use ($admin, $serverInstance): bool {
            return $mail->hasTo($admin->email)
                && $mail->serverInstance->is($serverInstance)
                && $mail->healthStatus === 'healthy';
        });

        $serverInstance->refresh();
        $this->assertSame('healthy', $serverInstance->last_health_alert_status);
        $this->assertSame(Date::now()->toDateTimeString(), $serverInstance->last_health_alerted_at?->toDateTimeString());
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
}
