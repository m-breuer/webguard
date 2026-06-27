<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Enums\NotificationType;
use App\Enums\TeamRole;
use App\Mail\StatusPageStatusUpdateMail;
use App\Mail\StatusPageSubscriptionConfirmationMail;
use App\Mail\TeamInvitationMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\StatusPageSubscriber;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailableContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_team_invitation_mail_exposes_subject_view_and_accept_url(): void
    {
        $inviter = User::factory()->create();
        $team = Team::factory()->create([
            'name' => 'Operations',
            'created_by_user_id' => $inviter->id,
        ]);
        $invitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'new-member@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'invite-token'),
            'invited_by_user_id' => $inviter->id,
            'expires_at' => now()->addDay(),
        ]);

        $mail = new TeamInvitationMail($invitation, 'invite-token');

        $this->assertSame(__('team.mail.invitation.subject', ['team' => 'Operations']), $mail->envelope()->subject);
        $this->assertSame('mail.team-invitation', $mail->content()->view);
        $this->assertSame(
            route('team-invitations.accept', ['token' => 'invite-token']),
            $mail->content()->with['acceptUrl']
        );
    }

    public function test_status_page_subscription_confirmation_mail_exposes_confirmation_contract(): void
    {
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'name' => 'Public API',
            'public_label_enabled' => true,
        ]);
        $subscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'subscriber@example.com',
            'confirmation_token_hash' => StatusPageSubscriber::hashToken('confirm-token'),
            'unsubscribe_token' => 'unsubscribe-token',
        ]);

        $mail = new StatusPageSubscriptionConfirmationMail($subscriber, 'confirm-token');

        $this->assertSame(__('mail.status_page_subscription_confirmation.subject', ['monitoringName' => 'Public API']), $mail->envelope()->subject);
        $this->assertSame('mail.status-page-subscription-confirmation', $mail->content()->view);
        $this->assertSame($subscriber->id, $mail->content()->with['subscriber']->id);
        $this->assertSame($monitoring->id, $mail->content()->with['monitoring']->id);
        $this->assertSame(
            route('public-label.subscribers.confirm', ['monitoring' => $monitoring, 'token' => 'confirm-token']),
            $mail->content()->with['confirmUrl']
        );
        $this->assertSame([], $mail->attachments());
    }

    public function test_status_page_status_update_mail_exposes_status_and_unsubscribe_contract(): void
    {
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'name' => 'Public API',
            'public_label_enabled' => true,
        ]);
        $subscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'subscriber@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => now(),
        ]);
        $notification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => false,
            'sent' => true,
        ]);

        $mail = new StatusPageStatusUpdateMail($subscriber, $notification, 'down');

        $this->assertSame(__('mail.status_page_status_update.subject', [
            'monitoringName' => 'Public API',
            'status' => 'DOWN',
        ]), $mail->envelope()->subject);
        $this->assertSame('mail.status-page-status-update', $mail->content()->view);
        $this->assertSame($monitoring->id, $mail->content()->with['monitoring']->id);
        $this->assertSame($notification->id, $mail->content()->with['notification']->id);
        $this->assertSame('down', $mail->content()->with['status']);
        $this->assertSame('DOWN', $mail->content()->with['statusLabel']);
        $this->assertSame(route('public-label', $monitoring), $mail->content()->with['statusPageUrl']);
        $this->assertSame(
            route('public-label.subscribers.unsubscribe', ['monitoring' => $monitoring, 'token' => 'unsubscribe-token']),
            $mail->content()->with['unsubscribeUrl']
        );
        $this->assertSame([], $mail->attachments());
    }
}
