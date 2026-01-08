<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\MonitoringNotification;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class DeleteOldGuestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:delete-old-guest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all notifications for guest users older than one week';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = MonitoringNotification::query()
            ->whereHas('monitoring.user', function (Builder $builder) {
                $builder->where('role', UserRole::GUEST);
            })
            ->where('created_at', '<', now()->subWeek())
            ->delete();

        $this->info("Deleted {$deleted} old guest notifications.");

        return Command::SUCCESS;
    }
}
