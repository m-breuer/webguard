<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\MonitoringNotification;
use App\Models\StatusPageSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusPageStatusUpdateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public StatusPageSubscriber $subscriber,
        public MonitoringNotification $notification,
        public string $status
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.status_page_status_update.subject', [
                'monitoringName' => $this->subscriber->monitoring->name,
                'status' => mb_strtoupper($this->status),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.status-page-status-update',
            with: [
                'monitoring' => $this->subscriber->monitoring,
                'notification' => $this->notification,
                'status' => $this->status,
                'statusLabel' => mb_strtoupper($this->status),
                'statusPageUrl' => route('public-label', $this->subscriber->monitoring),
                'unsubscribeUrl' => route('public-label.subscribers.unsubscribe', [
                    'monitoring' => $this->subscriber->monitoring,
                    'token' => $this->subscriber->unsubscribe_token,
                ]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
