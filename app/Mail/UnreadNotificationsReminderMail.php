<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnreadNotificationsReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $unreadNotificationsCount;

    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(int $unreadNotificationsCount, User $user)
    {
        $this->unreadNotificationsCount = $unreadNotificationsCount;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.unread_notifications_reminder.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
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
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
