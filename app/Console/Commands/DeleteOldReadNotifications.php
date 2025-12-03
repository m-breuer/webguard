<?php

namespace App\Console\Commands;

use App\Models\MonitoringNotification;
use Illuminate\Console\Command;

class DeleteOldReadNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:delete-old-read';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete read notifications that are older than one month.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Deleting old read notifications...');

        $deletedCount = 0;
        MonitoringNotification::query()->where('read', true)
            ->where('created_at', '<', now()->subMonth())
            ->chunkById(250, function ($notifications) use (&$deletedCount) {
                $deletedCount += $notifications->count();
                MonitoringNotification::query()->whereIn('id', $notifications->pluck('id'))->delete();
            });

        $this->info("Deleted {$deletedCount} old read notifications.");

        return Command::SUCCESS;
    }
}
