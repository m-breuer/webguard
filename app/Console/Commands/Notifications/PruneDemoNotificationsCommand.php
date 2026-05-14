<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\UserRole;
use App\Models\MonitoringNotification;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class PruneDemoNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all notifications for demo users older than one week';

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
