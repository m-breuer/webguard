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

class WeeklyMonitoringDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $digest
     */
    public function __construct(public array $digest, public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.weekly_monitoring_digest.subject', [
                'from' => $this->digest['period_start']->toDateString(),
                'to' => $this->digest['period_end']->toDateString(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.weekly-monitoring-digest',
            with: [
                'digest' => $this->digest,
                'user' => $this->user,
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
