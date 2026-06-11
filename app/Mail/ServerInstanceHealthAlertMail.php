<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServerInstanceHealthAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ServerInstance $serverInstance,
        public string $healthStatus,
        public User $admin
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.server_instance_health_alert.subject', [
                'instanceCode' => $this->serverInstance->code,
                'status' => mb_strtoupper($this->healthStatus),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.server-instance-health-alert',
            with: [
                'admin' => $this->admin,
                'serverInstance' => $this->serverInstance,
                'healthStatus' => $this->healthStatus,
                'healthStatusLabel' => __('admin.server_instances.health.' . $this->healthStatus),
                'isRecovery' => $this->healthStatus === 'healthy',
                'staleAfterMinutes' => max(1, (int) config('monitoring.instance_stale_after_minutes', 10)),
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
