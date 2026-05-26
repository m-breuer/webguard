<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\UserRole;
use App\Models\MonitoringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

#[Description('Delete all notifications for demo users older than one week')]
#[Signature('notifications:prune-demo')]
class PruneDemoNotificationsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = MonitoringNotification::query()
            ->whereHas('monitoring.user', function (Builder $builder) {
                $builder->where('role', UserRole::DEMO);
            })
            ->where('created_at', '<', now()->subWeek())
            ->delete();

        $this->info("Deleted {$deleted} old demo user notifications.");

        return Command::SUCCESS;
    }
}
