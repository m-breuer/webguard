<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\NotificationEventType;
use App\Mail\StatusPageStatusUpdateMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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

            $identifier = MonitoringNotification::extractStatusChangeIdentifierFromMessage($notification->message);
            $eventType = $identifier === 'down'
                ? NotificationEventType::INCIDENT
                : NotificationEventType::RECOVERY;

            $this->dispatchStatusPageSubscriberEmails($monitoring, $notification, $identifier);

            if (! $monitoring->notification_on_failure) {
                $notification->update(['sent' => true]);

                continue;
            }

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

            $this->notificationRouter->dispatch($user, $payload, $monitoring->notification_channels);
            $notification->update(['sent' => true]);
        }

        return Command::SUCCESS;
    }

    private function dispatchStatusPageSubscriberEmails(
        Monitoring $monitoring,
        MonitoringNotification $notification,
        string $status
    ): void {
        if (! $monitoring->public_label_enabled || ! in_array($status, ['down', 'up'], true)) {
            return;
        }

        $monitoring->statusPageSubscribers()
            ->verified()
            ->each(function ($subscriber) use ($notification, $status): void {
                Mail::to($subscriber->email)->send(
                    new StatusPageStatusUpdateMail($subscriber, $notification, $status)
                );
            });
    }
}
