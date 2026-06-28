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
        MonitoringNotification::query()
            ->withoutGlobalScopes()
            ->where('created_at', '<', now()->subMonth())
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('monitoring_notification_states')
                    ->whereColumn('monitoring_notification_states.monitoring_notification_id', 'monitoring_notifications.id');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('monitoring_notification_states')
                    ->whereColumn('monitoring_notification_states.monitoring_notification_id', 'monitoring_notifications.id')
                    ->whereNull('monitoring_notification_states.read_at');
            })
            ->chunkById(250, function ($notifications) use (&$deletedCount) {
                $deletedCount += $notifications->count();
                MonitoringNotification::query()->withoutGlobalScopes()->whereIn('id', $notifications->pluck('id'))->delete();
            });

        $this->info("Deleted {$deletedCount} old read notifications.");

        return Command::SUCCESS;
    }
}
