<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogAdminTest extends TestCase
{
    public function test_only_admins_can_view_audit_logs(): void
    {
        Package::factory()->create();
        $user = User::factory()->create(['role' => UserRole::REGULAR->value]);

        $this->actingAs($user)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_audit_logs_from_dashboard(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user = User::factory()->create(['email' => 'audited-user@example.test']);
        Activity::query()->delete();

        activity('user')
            ->causedBy($admin)
            ->performedOn($user)
            ->event('updated')
            ->withChanges([
                'attributes' => [
                    'name' => 'Audited User',
                    'password' => 'raw-password',
                ],
                'old' => [
                    'name' => 'Old User',
                    'password' => 'old-password',
                ],
            ])
            ->log('user_updated');

        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSeeText(__('admin.dashboard.activity_logs.heading'));
        $dashboardResponse->assertSeeHtml(route('admin.activity-logs.index'));

        $testResponse = $this->actingAs($admin)->get(route('admin.activity-logs.index'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('admin.activity_logs.title'));
        $testResponse->assertSeeText($admin->email);
        $testResponse->assertSeeText('user');
        $testResponse->assertSeeText('updated');
        $testResponse->assertSeeText('user_updated');
        $testResponse->assertSeeText(__('admin.activity_logs.subject_types.user'));
        $testResponse->assertSeeText($user->id);
        $testResponse->assertSeeText('[redacted]');
        $testResponse->assertDontSeeText('raw-password');
        $testResponse->assertDontSeeText('old-password');
    }

    public function test_admin_can_filter_audit_logs(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        Activity::query()->delete();

        activity('user')
            ->causedBy($admin)
            ->performedOn($user)
            ->event('updated')
            ->withChanges(['attributes' => ['name' => 'Visible User']])
            ->log('user_updated');

        activity('monitoring')
            ->causedBy($user)
            ->performedOn($monitoring)
            ->event('created')
            ->withChanges(['attributes' => ['name' => 'Hidden Monitoring']])
            ->log('monitoring_created');

        $testResponse = $this->actingAs($admin)->get(route('admin.activity-logs.index', [
            'log_name' => 'user',
            'event' => 'updated',
            'causer_id' => $admin->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]));

        $testResponse->assertOk();
        $testResponse->assertSeeText('user_updated');
        $testResponse->assertSeeText('Visible User');
        $testResponse->assertDontSeeText('monitoring_created');
        $testResponse->assertDontSeeText('Hidden Monitoring');
    }
}
