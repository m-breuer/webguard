<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\StatusPageSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PublicStatusPageSubscriptionConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public StatusPageSubscription $subscription, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.public_status_page_subscription_confirmation.subject', [
                'statusPageName' => $this->subscription->statusPage->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.public-status-page-subscription-confirmation',
            with: [
                'subscription' => $this->subscription,
                'statusPage' => $this->subscription->statusPage,
                'confirmUrl' => route('public-status-pages.subscribers.confirm', [
                    'statusPage' => $this->subscription->statusPage->slug,
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
