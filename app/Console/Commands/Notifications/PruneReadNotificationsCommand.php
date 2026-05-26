<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Models\MonitoringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Delete read notifications that are older than one month.')]
#[Signature('notifications:prune-read')]
class PruneReadNotificationsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Deleting old read notifications...');

        $deletedCount = 0;
        MonitoringNotification::query()->read()
            ->where('created_at', '<', now()->subMonth())
            ->chunkById(250, function ($notifications) use (&$deletedCount) {
                $deletedCount += $notifications->count();
                MonitoringNotification::query()->whereIn('id', $notifications->pluck('id'))->delete();
            });

        $this->info("Deleted {$deletedCount} old read notifications.");

        return Command::SUCCESS;
    }
}
