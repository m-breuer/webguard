<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\StatusPageSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusPageSubscriptionConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public StatusPageSubscriber $subscriber, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.status_page_subscription_confirmation.subject', [
                'monitoringName' => $this->subscriber->monitoring->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.status-page-subscription-confirmation',
            with: [
                'subscriber' => $this->subscriber,
                'monitoring' => $this->subscriber->monitoring,
                'confirmUrl' => route('public-status-pages.subscribers.confirm', [
                    'statusPage' => $this->subscriber->monitoring,
                    'token' => $this->token,
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
