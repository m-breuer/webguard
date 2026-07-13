<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Enums\NotificationType;
use App\Enums\TeamRole;
use App\Mail\PublicStatusPageStatusUpdateMail;
use App\Mail\PublicStatusPageSubscriptionConfirmationMail;
use App\Mail\StatusPageStatusUpdateMail;
use App\Mail\StatusPageSubscriptionConfirmationMail;
use App\Mail\TeamInvitationMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Models\StatusPageSubscription;
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
        $teamInvitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => 'new-member@example.com',
            'role' => TeamRole::MEMBER,
            'token_hash' => hash('sha256', 'invite-token'),
            'invited_by_user_id' => $inviter->id,
            'expires_at' => now()->addDay(),
        ]);

        $teamInvitationMail = new TeamInvitationMail($teamInvitation, 'invite-token');

        $this->assertSame(__('team.mail.invitation.subject', ['team' => 'Operations']), $teamInvitationMail->envelope()->subject);
        $this->assertSame('mail.team-invitation', $teamInvitationMail->content()->view);
        $this->assertSame(
            route('team-invitations.accept', ['token' => 'invite-token']),
            $teamInvitationMail->content()->with['acceptUrl']
        );
    }

    public function test_status_page_subscription_confirmation_mail_exposes_confirmation_contract(): void
    {
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'name' => 'Public API',
            'public_label_enabled' => true,
        ]);
        $statusPageSubscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'subscriber@example.com',
            'confirmation_token_hash' => StatusPageSubscriber::hashToken('confirm-token'),
            'unsubscribe_token' => 'unsubscribe-token',
        ]);

        $statusPageSubscriptionConfirmationMail = new StatusPageSubscriptionConfirmationMail($statusPageSubscriber, 'confirm-token');

        $this->assertSame(__('mail.status_page_subscription_confirmation.subject', ['monitoringName' => 'Public API']), $statusPageSubscriptionConfirmationMail->envelope()->subject);
        $this->assertSame('mail.status-page-subscription-confirmation', $statusPageSubscriptionConfirmationMail->content()->view);
        $this->assertSame($statusPageSubscriber->id, $statusPageSubscriptionConfirmationMail->content()->with['subscriber']->id);
        $this->assertSame($monitoring->id, $statusPageSubscriptionConfirmationMail->content()->with['monitoring']->id);
        $this->assertSame(
            route('public-label.subscribers.confirm', ['monitoring' => $monitoring, 'token' => 'confirm-token']),
            $statusPageSubscriptionConfirmationMail->content()->with['confirmUrl']
        );
        $this->assertSame([], $statusPageSubscriptionConfirmationMail->attachments());
    }

    public function test_status_page_status_update_mail_exposes_status_and_unsubscribe_contract(): void
    {
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'name' => 'Public API',
            'public_label_enabled' => true,
        ]);
        $statusPageSubscriber = StatusPageSubscriber::query()->create([
            'monitoring_id' => $monitoring->id,
            'email' => 'subscriber@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => now(),
        ]);
        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => false,
            'sent' => true,
        ]);

        $statusPageStatusUpdateMail = new StatusPageStatusUpdateMail($statusPageSubscriber, $monitoringNotification, 'down');

        $this->assertSame(__('mail.status_page_status_update.subject', [
            'monitoringName' => 'Public API',
            'status' => 'DOWN',
        ]), $statusPageStatusUpdateMail->envelope()->subject);
        $this->assertSame('mail.status-page-status-update', $statusPageStatusUpdateMail->content()->view);
        $this->assertSame($monitoring->id, $statusPageStatusUpdateMail->content()->with['monitoring']->id);
        $this->assertSame($monitoringNotification->id, $statusPageStatusUpdateMail->content()->with['notification']->id);
        $this->assertSame('down', $statusPageStatusUpdateMail->content()->with['status']);
        $this->assertSame('DOWN', $statusPageStatusUpdateMail->content()->with['statusLabel']);
        $this->assertSame(route('public-label', $monitoring), $statusPageStatusUpdateMail->content()->with['statusPageUrl']);
        $this->assertSame(
            route('public-label.subscribers.unsubscribe', ['monitoring' => $monitoring, 'token' => 'unsubscribe-token']),
            $statusPageStatusUpdateMail->content()->with['unsubscribeUrl']
        );
        $this->assertSame([], $statusPageStatusUpdateMail->attachments());
    }

    public function test_public_status_page_subscription_confirmation_mail_exposes_confirmation_contract(): void
    {
        $statusPage = StatusPage::query()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageSubscription = StatusPageSubscription::query()->create([
            'status_page_id' => $statusPage->id,
            'email' => 'subscriber@example.com',
            'confirmation_token_hash' => StatusPageSubscription::hashToken('confirm-token'),
            'unsubscribe_token' => 'unsubscribe-token',
        ]);

        $publicStatusPageSubscriptionConfirmationMail = new PublicStatusPageSubscriptionConfirmationMail($statusPageSubscription, 'confirm-token');

        $this->assertSame(__('mail.public_status_page_subscription_confirmation.subject', [
            'statusPageName' => 'Acme Status',
        ]), $publicStatusPageSubscriptionConfirmationMail->envelope()->subject);
        $this->assertSame('mail.public-status-page-subscription-confirmation', $publicStatusPageSubscriptionConfirmationMail->content()->view);
        $this->assertSame($statusPageSubscription->id, $publicStatusPageSubscriptionConfirmationMail->content()->with['subscription']->id);
        $this->assertSame($statusPage->id, $publicStatusPageSubscriptionConfirmationMail->content()->with['statusPage']->id);
        $this->assertSame(route('public-status-pages.subscribers.confirm', [
            'statusPage' => $statusPage,
            'token' => 'confirm-token',
        ]), $publicStatusPageSubscriptionConfirmationMail->content()->with['confirmUrl']);
        $this->assertSame([], $publicStatusPageSubscriptionConfirmationMail->attachments());
    }

    public function test_public_status_page_status_update_mail_exposes_status_and_unsubscribe_contract(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Checkout API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageSubscription = StatusPageSubscription::query()->create([
            'status_page_id' => $statusPage->id,
            'email' => 'subscriber@example.com',
            'confirmation_token_hash' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'verified_at' => now(),
        ]);
        $monitoringNotification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => NotificationType::STATUS_CHANGE,
            'message' => 'Monitoring is down',
            'read' => false,
            'sent' => true,
        ]);

        $publicStatusPageStatusUpdateMail = new PublicStatusPageStatusUpdateMail($statusPageSubscription, $monitoring, $monitoringNotification, 'down');

        $this->assertSame(__('mail.public_status_page_status_update.subject', [
            'statusPageName' => 'Acme Status',
            'monitoringName' => 'Checkout API',
            'status' => 'DOWN',
        ]), $publicStatusPageStatusUpdateMail->envelope()->subject);
        $this->assertSame('mail.public-status-page-status-update', $publicStatusPageStatusUpdateMail->content()->view);
        $this->assertSame($statusPage->id, $publicStatusPageStatusUpdateMail->content()->with['statusPage']->id);
        $this->assertSame($monitoring->id, $publicStatusPageStatusUpdateMail->content()->with['monitoring']->id);
        $this->assertSame($monitoringNotification->id, $publicStatusPageStatusUpdateMail->content()->with['notification']->id);
        $this->assertSame('down', $publicStatusPageStatusUpdateMail->content()->with['status']);
        $this->assertSame('DOWN', $publicStatusPageStatusUpdateMail->content()->with['statusLabel']);
        $this->assertSame(route('public-status-pages.show', $statusPage), $publicStatusPageStatusUpdateMail->content()->with['statusPageUrl']);
        $this->assertSame(route('public-status-pages.subscribers.unsubscribe', [
            'statusPage' => $statusPage,
            'token' => 'unsubscribe-token',
        ]), $publicStatusPageStatusUpdateMail->content()->with['unsubscribeUrl']);
        $this->assertSame([], $publicStatusPageStatusUpdateMail->attachments());
    }
}
