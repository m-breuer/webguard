<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Jobs\SendStatusChangeNotificationEmail as SendStatusChangeNotificationEmailJob;
use App\Models\MonitoringNotification;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class SendStatusChangeNotificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-status-change-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications for status changes.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sending status change notification emails...');

        // Filter on monitorings where users have enabled email notifications
        $notifications = MonitoringNotification::query()->where('type', NotificationType::STATUS_CHANGE)
            ->where('sent', false)
            ->whereHas('monitoring', function (Builder $builder) {
                $builder->where('email_notification_on_failure', true);
            })
            ->get();

        foreach ($notifications as $notification) {
            $user = $notification->monitoring->user;

            if ($user && $user->email) {
                dispatch(new SendStatusChangeNotificationEmailJob($notification));
                $this->info("Dispatched email job for notification {$notification->id}");
            }
        }

        $this->info('Finished sending status change notification emails.');

        return Command::SUCCESS;
    }
}
