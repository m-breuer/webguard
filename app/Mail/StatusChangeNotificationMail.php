<?php

namespace App\Mail;

use App\Models\MonitoringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusChangeNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public MonitoringNotification $notification;

    /**
     * Create a new message instance.
     */
    public function __construct(MonitoringNotification $monitoringNotification)
    {
        $this->notification = $monitoringNotification;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.status_change_notification.subject', ['monitoringName' => $this->notification->monitoring->name]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.status-change-notification',
            with: [
                'notification' => $this->notification,
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
