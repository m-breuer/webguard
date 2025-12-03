<?php

namespace App\Mail;

use App\Models\Monitoring;
use App\Models\MonitoringSslResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SslExpiryWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public MonitoringSslResult $sslResult;

    public Monitoring $monitoring;

    /**
     * Create a new message instance.
     */
    public function __construct(MonitoringSslResult $monitoringSslResult, Monitoring $monitoring)
    {
        $this->sslResult = $monitoringSslResult;
        $this->monitoring = $monitoring;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.ssl_expiry_warning.subject', ['monitoringName' => $this->monitoring->name]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.ssl-expiry-warning',
            with: [
                'monitoringName' => $this->monitoring->name,
                'monitoringTarget' => $this->monitoring->target,
                'expiryDate' => $this->sslResult->expires_at->format('Y-m-d'),
                'monitoringUrl' => route('monitorings.show', $this->monitoring),
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
