<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\NotificationEventType;
use App\Mail\StatusPageStatusUpdateMail;
use App\Models\Monitoring;
use App\Models\MonitoringNotification;
use App\Services\Notifications\MonitoringNotificationPreferenceResolver;
use App\Services\Notifications\MonitoringNotificationStateService;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Description('Dispatch status change notifications to configured channels.')]
#[Signature('notifications:dispatch-status-changes')]
class DispatchStatusChangeNotificationsCommand extends Command
{
    public function __construct(
        private readonly NotificationRouter $notificationRouter,
        private readonly MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver,
        private readonly MonitoringNotificationStateService $monitoringNotificationStateService
    ) {
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
            ->with(['monitoring.user', 'monitoring.team.users'])
            ->get();

        foreach ($notifications as $notification) {
            $monitoring = $notification->monitoring;

            if (! $monitoring) {
                $notification->update(['sent' => true]);

                continue;
            }

            $identifier = MonitoringNotification::extractStatusChangeIdentifierFromMessage($notification->message);
            $eventType = $identifier === 'down'
                ? NotificationEventType::INCIDENT
                : NotificationEventType::RECOVERY;

            $this->dispatchStatusPageSubscriberEmails($monitoring, $notification, $identifier);

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

            $this->monitoringNotificationPreferenceResolver
                ->recipientsFor($monitoring)
                ->each(function (array $recipient) use ($notification, $payload): void {
                    $user = $recipient['user'];
                    $preference = $recipient['preference'];

                    $this->monitoringNotificationStateService->ensureState($notification, $user);

                    if (! $preference->notification_on_failure) {
                        return;
                    }

                    $this->notificationRouter->dispatch($user, $payload, $preference->notification_channels);
                    $this->monitoringNotificationStateService->markSent($notification, $user);
                });

            $notification->update(['sent' => true]);
        }

        return Command::SUCCESS;
    }

    private function dispatchStatusPageSubscriberEmails(
        Monitoring $monitoring,
        MonitoringNotification $monitoringNotification,
        string $status
    ): void {
        if (! $monitoring->public_label_enabled || ! in_array($status, ['down', 'up'], true)) {
            return;
        }

        $monitoring->statusPageSubscribers()
            ->verified()
            ->each(function ($subscriber) use ($monitoringNotification, $status): void {
                Mail::to($subscriber->email)->send(
                    new StatusPageStatusUpdateMail($subscriber, $monitoringNotification, $status)
                );
            });
    }
}
