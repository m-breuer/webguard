<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\UserRole;
use App\Mail\UnreadNotificationsReminderMail;
use App\Models\User;
use App\Services\NotificationBoardService;
use Illuminate\Console\Command;
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
    protected $description = 'Sends daily email reminders to non-guest users with unread board notifications.';

    public function __construct(private readonly NotificationBoardService $notificationBoardService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $unreadCounts = $this->notificationBoardService->getUnreadNotificationCountsByUser();

        if ($unreadCounts->isEmpty()) {
            return Command::SUCCESS;
        }

        $users = User::query()
            ->whereIn('id', $unreadCounts->keys())
            ->where('role', '!=', UserRole::GUEST->value)
            ->get();

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
                Log::error('Failed to send unread notifications reminder.', [
                    'user_id' => $user->id,
                    'exception' => $throwable->getMessage(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
