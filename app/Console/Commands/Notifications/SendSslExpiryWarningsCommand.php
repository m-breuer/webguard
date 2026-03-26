<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\NotificationEventType;
use App\Enums\NotificationType;
use App\Models\MonitoringNotification;
use App\Models\MonitoringSslResult;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendSslExpiryWarningsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-ssl-expiry-warnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks SSL certificates and dispatches channel notifications.';

    public function __construct(private readonly NotificationRouter $notificationRouter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sslResults = MonitoringSslResult::query()
            ->where(function ($builder): void {
                $builder->where('expires_at', '<=', now()->addDays(7))
                    ->orWhere('is_valid', false);
            })
            ->with(['monitoring.user'])
            ->get();

        foreach ($sslResults as $sslResult) {
            $monitoring = $sslResult->monitoring;
            $user = $monitoring->user;
            if (! $monitoring) {
                continue;
            }
            if (! $user) {
                continue;
            }

            if (! $monitoring->notification_on_failure) {
                continue;
            }

            $eventType = (! $sslResult->is_valid || $sslResult->expires_at->lte(now()))
                ? NotificationEventType::SSL_EXPIRED
                : NotificationEventType::SSL_EXPIRING;

            $cacheKey = sprintf('ssl_notification_%s_%s_%s', $eventType->value, $sslResult->id, now()->format('Y-m-d'));

            if (Cache::has($cacheKey)) {
                continue;
            }

            $message = $eventType === NotificationEventType::SSL_EXPIRED
                ? 'SSL_EXPIRED'
                : 'SSL_EXPIRING';

            $notification = MonitoringNotification::query()->create([
                'monitoring_id' => $monitoring->id,
                'type' => NotificationType::SSL_EXPIRY,
                'message' => $message,
                'read' => false,
                'sent' => false,
            ]);

            $payload = new NotificationPayload(
                eventType: $eventType,
                title: $eventType === NotificationEventType::SSL_EXPIRED
                    ? 'SSL certificate expired'
                    : 'SSL certificate expiring soon',
                message: sprintf(
                    '%s (%s) certificate expires at %s.',
                    $monitoring->name,
                    $monitoring->target,
                    $sslResult->expires_at->toDateTimeString()
                ),
                severity: $eventType === NotificationEventType::SSL_EXPIRED ? 'critical' : 'warning',
                monitoringId: $monitoring->id,
                monitoringName: $monitoring->name,
                monitoringTarget: $monitoring->target,
                occurredAt: now(),
                meta: [
                    'ssl_result_id' => $sslResult->id,
                    'notification_id' => $notification->id,
                    'expires_at' => $sslResult->expires_at->toIso8601String(),
                ],
            );

            $this->notificationRouter->dispatch($user, $payload);
            $notification->update(['sent' => true]);
            Cache::put($cacheKey, true, now()->addHours(23));
        }

        return Command::SUCCESS;
    }
}
