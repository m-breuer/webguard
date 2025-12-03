<?php

namespace App\Jobs;

use App\Mail\UnreadNotificationsReminderMail;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendUnreadNotificationsReminder
 *
 * This job is responsible for sending daily email reminders to users with unread notifications.
 */
class SendUnreadNotificationsReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user, public int $unreadNotificationsCount) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->user && $this->user->email) {
            try {
                Mail::to($this->user->email)->send(new UnreadNotificationsReminderMail($this->unreadNotificationsCount, $this->user));
            } catch (Exception $e) {
                Log::error("Failed to send unread notifications reminder email to {$this->user->email}: ".$e->getMessage());
            }
        }
    }
}
