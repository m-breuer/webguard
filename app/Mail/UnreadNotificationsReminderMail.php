<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnreadNotificationsReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public int $unreadNotificationsCount, public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.unread_notifications_reminder.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.unread-notifications-reminder',
            with: [
                'unreadNotificationsCount' => $this->unreadNotificationsCount,
                'user' => $this->user,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
