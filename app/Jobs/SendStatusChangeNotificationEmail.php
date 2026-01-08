<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\StatusChangeNotificationMail;
use App\Models\MonitoringNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendStatusChangeNotificationEmail
 *
 * This job is responsible for sending email notifications when a monitoring's status changes.
 */
class SendStatusChangeNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public MonitoringNotification $notification) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $monitoring = $this->notification->monitoring;
        $user = $monitoring->user;

        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new StatusChangeNotificationMail($this->notification));

                $this->notification->sent = true;
                $this->notification->save();
            } catch (Exception $e) {
                Log::error("Failed to send email for notification {$this->notification->id}: " . $e->getMessage());
            }
        }
    }
}
