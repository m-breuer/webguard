<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Monitoring;
use App\Models\StatusPageSubscription;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PublicStatusPageMaintenanceScheduledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     */
    public function __construct(
        public StatusPageSubscription $subscription,
        public Collection $monitorings,
        public CarbonInterface $startsAt,
        public ?CarbonInterface $endsAt,
        public string $timezone,
        public bool $recurring,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.public_status_page_maintenance_scheduled.subject', [
                'statusPageName' => $this->subscription->statusPage->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.public-status-page-maintenance-scheduled',
            with: [
                'statusPage' => $this->subscription->statusPage,
                'monitorings' => $this->monitorings,
                'startsAt' => $this->formatDate($this->startsAt),
                'endsAt' => $this->endsAt === null ? null : $this->formatDate($this->endsAt),
                'timezone' => $this->timezone,
                'recurring' => $this->recurring,
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

    private function formatDate(CarbonInterface $date): string
    {
        return $date->copy()
            ->setTimezone($this->timezone)
            ->locale(app()->getLocale())
            ->translatedFormat('d.m.Y H:i');
    }
}
