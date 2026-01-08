<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendUnreadNotificationsReminder as SendUnreadNotificationsReminderJob;
use App\Models\MonitoringNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class SendUnreadNotificationsReminder extends Command
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
        $users = User::all();

        foreach ($users as $user) {
            $unreadNotificationsCount = MonitoringNotification::query()->whereHas('monitoring', function (Builder $builder) use ($user) {
                $builder->where('user_id', $user->id);
            })
                ->where('read', false)
                ->count();

            if ($unreadNotificationsCount > 0) {
                $this->info("Sending reminder to {$user->email} for {$unreadNotificationsCount} unread notifications.");

                dispatch(new SendUnreadNotificationsReminderJob($user, $unreadNotificationsCount));
            }
        }

        $this->info('Unread notifications reminder process completed.');

        return Command::SUCCESS;
    }
}
