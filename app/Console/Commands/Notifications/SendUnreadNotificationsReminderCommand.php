<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Jobs\SendUnreadNotificationsReminder as SendUnreadNotificationsReminderJob;
use App\Models\MonitoringNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendUnreadNotificationsReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:remind-unread';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends daily reminders to users with unread notifications.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for users with unread notifications...');

        $unreadCounts = MonitoringNotification::query()
            ->where('read', false)
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->select('monitorings.user_id', DB::raw('count(*) as total'))
            ->groupBy('monitorings.user_id')
            ->pluck('total', 'user_id');

        if ($unreadCounts->isEmpty()) {
            $this->info('No users with unread notifications found.');

            return Command::SUCCESS;
        }

        $users = User::query()->find($unreadCounts->keys());

        foreach ($users as $user) {
            $unreadNotificationsCount = $unreadCounts->get($user->id);
            if ($unreadNotificationsCount > 0) {
                $this->info("Sending reminder to {$user->email} for {$unreadNotificationsCount} unread notifications.");
                dispatch(new SendUnreadNotificationsReminderJob($user, $unreadNotificationsCount));
            }
        }

        $this->info('Unread notifications reminder process completed.');

        return Command::SUCCESS;
    }
}
