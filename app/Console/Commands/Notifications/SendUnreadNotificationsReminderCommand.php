<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Mail\UnreadNotificationsReminderMail;
use App\Models\MonitoringNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendUnreadNotificationsReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notifications:remind-unread-weekly';

    /**
     * @var string
     */
    protected $description = 'Sends weekly email reminders to users with unread board notifications.';

    public function handle(): int
    {
        $unreadCounts = MonitoringNotification::query()
            ->unread()
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->select('monitorings.user_id', DB::raw('count(*) as total'))
            ->groupBy('monitorings.user_id')
            ->pluck('total', 'user_id');

        if ($unreadCounts->isEmpty()) {
            return Command::SUCCESS;
        }

        $users = User::query()->whereIn('id', $unreadCounts->keys())->get();

        foreach ($users as $user) {
            $unreadNotificationsCount = (int) ($unreadCounts->get($user->id) ?? 0);
            if ($unreadNotificationsCount < 1) {
                continue;
            }
            if (blank($user->email)) {
                continue;
            }

            try {
                Mail::to($user->email)->send(
                    (new UnreadNotificationsReminderMail($unreadNotificationsCount, $user))
                        ->locale($user->locale ?? config('app.locale'))
                );
            } catch (Throwable $throwable) {
                Log::error('Failed to send weekly unread notifications reminder.', [
                    'user_id' => $user->id,
                    'exception' => $throwable->getMessage(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
