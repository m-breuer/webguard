<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Models\StatusPageSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PublicStatusPageStatusUpdateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public StatusPageSubscription $subscription,
        public Monitoring $monitoring,
        public MonitoringNotification $notification,
        public string $status
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.public_status_page_status_update.subject', [
                'statusPageName' => $this->subscription->statusPage->name,
                'monitoringName' => $this->monitoring->name,
                'status' => mb_strtoupper($this->status),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.public-status-page-status-update',
            with: [
                'statusPage' => $this->subscription->statusPage,
                'monitoring' => $this->monitoring,
                'notification' => $this->notification,
                'status' => $this->status,
                'statusLabel' => mb_strtoupper($this->status),
                'statusPageUrl' => route('public-status-pages.show', $this->subscription->statusPage),
                'unsubscribeUrl' => route('public-status-pages.subscribers.unsubscribe', [
                    'statusPage' => $this->subscription->statusPage,
                    'token' => $this->subscription->unsubscribe_token,
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
