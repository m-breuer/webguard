<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\NotificationEventType;
use App\Models\MonitoringNotification;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Console\Command;

class DispatchStatusChangeNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:dispatch-status-changes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch status change notifications to configured channels.';

    public function __construct(private readonly NotificationRouter $notificationRouter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $notifications = MonitoringNotification::query()
            ->statusChange()
            ->where('sent', false)
            ->with(['monitoring.user'])
            ->get();

        foreach ($notifications as $notification) {
            $monitoring = $notification->monitoring;
            $user = $monitoring?->user;

            if (! $monitoring || ! $user) {
                $notification->update(['sent' => true]);

                continue;
            }

            if (! $monitoring->notification_on_failure) {
                $notification->update(['sent' => true]);

                continue;
            }

            $identifier = MonitoringNotification::extractStatusChangeIdentifierFromMessage($notification->message);
            $eventType = $identifier === 'down'
                ? NotificationEventType::INCIDENT
                : NotificationEventType::RECOVERY;

            $payload = new NotificationPayload(
                eventType: $eventType,
                title: $eventType === NotificationEventType::INCIDENT
                    ? 'Monitoring incident'
                    : 'Monitoring recovered',
                message: sprintf(
                    '%s (%s) changed status to %s.',
                    $monitoring->name,
                    $monitoring->target,
                    mb_strtoupper($identifier)
                ),
                severity: $eventType === NotificationEventType::INCIDENT ? 'critical' : 'info',
                monitoringId: $monitoring->id,
                monitoringName: $monitoring->name,
                monitoringTarget: $monitoring->target,
                occurredAt: $notification->created_at,
                meta: [
                    'notification_id' => $notification->id,
                ],
            );

            $this->notificationRouter->dispatch($user, $payload);
            $notification->update(['sent' => true]);
        }

        return Command::SUCCESS;
    }
}
