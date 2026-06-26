<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TeamInvitation $invitation,
        public string $token
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('team.mail.invitation.subject', ['team' => $this->invitation->team->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.team-invitation',
            with: [
                'acceptUrl' => route('team-invitations.accept', ['token' => $this->token]),
            ],
        );
    }
}
