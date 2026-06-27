<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Teams;

use App\Enums\NotificationType;
use App\Enums\TeamRole;
use App\Mail\TeamInvitationMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\MonitoringNotificationPreference;
use App\Models\MonitoringNotificationState;
use App\Models\Package;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\TeamInvitationService;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamServiceEdgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_invite_normalizes_email_replaces_pending_invitation_and_rejects_members(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.com']);
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        $service = app(TeamInvitationService::class);

        $firstInvitation = $service->invite($team, $admin, ' New.User@Example.COM ', TeamRole::MEMBER);
        $secondInvitation = $service->invite($team, $admin, 'new.user@example.com', TeamRole::ADMIN);

        $this->assertDatabaseMissing('team_invitations', ['id' => $firstInvitation->id]);
        $this->assertSame('new.user@example.com', $secondInvitation->email);
        $this->assertSame(TeamRole::ADMIN, $secondInvitation->role);
        Mail::assertSent(TeamInvitationMail::class, 2);

        $this->expectException(ValidationException::class);
        $service->invite($team, $admin, 'member@example.com', TeamRole::MEMBER);
    }

    public function test_accept_rejects_email_mismatch_and_accepts_matching_pending_invitation(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $service = app(TeamInvitationService::class);
        $service->invite($team, $admin, $invitedUser->email, TeamRole::MEMBER);
        $token = $this->latestInvitationTokenFromMail();

        try {
            $service->accept($token, $otherUser);
            $this->fail('Expected an email mismatch validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $acceptedTeam = $service->accept($token, $invitedUser);

        $this->assertTrue($acceptedTeam->is($team));
        $this->assertDatabaseHas('team_memberships', [
            'team_id' => $team->id,
            'user_id' => $invitedUser->id,
            'role' => TeamRole::MEMBER->value,
        ]);
        $this->assertDatabaseMissing('team_invitations', [
            'team_id' => $team->id,
            'accepted_at' => null,
        ]);
    }

    public function test_removing_member_deletes_monitoring_preferences_and_notification_states(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $admin->id]);
        $team->memberships()->create(['user_id' => $admin->id, 'role' => TeamRole::ADMIN]);
        $membership = $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::MEMBER]);
        $monitoring = Monitoring::factory()->create(['user_id' => null, 'team_id' => $team->id]);
        $notification = MonitoringNotification::query()->withoutGlobalScopes()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => false,
            'sent' => false,
        ]);
        MonitoringNotificationPreference::query()->create([
            'monitoring_id' => $monitoring->id,
            'user_id' => $member->id,
            'notification_on_failure' => true,
            'notification_channels' => ['mail'],
            'ssl_expiry_warning_days' => 30,
        ]);
        MonitoringNotificationState::query()->where([
            'monitoring_notification_id' => $notification->id,
            'user_id' => $member->id,
        ])->firstOrFail();

        app(TeamMembershipService::class)->remove($membership);

        $this->assertDatabaseMissing('team_memberships', ['id' => $membership->id]);
        $this->assertDatabaseMissing('monitoring_notification_preferences', [
            'monitoring_id' => $monitoring->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseMissing('monitoring_notification_states', [
            'monitoring_notification_id' => $notification->id,
            'user_id' => $member->id,
        ]);
    }

    private function latestInvitationTokenFromMail(): string
    {
        $mail = Mail::sent(TeamInvitationMail::class)->last();

        $acceptUrl = $mail->content()->with['acceptUrl'];
        $segments = explode('/', trim((string) parse_url($acceptUrl, PHP_URL_PATH), '/'));

        return $segments[count($segments) - 2];
    }
}
