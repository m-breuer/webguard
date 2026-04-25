<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\NotificationEventType;
use App\Enums\NotificationType;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringSslResult;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Carbon\CarbonInterface;
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
    protected $description = 'Checks SSL certificates and domains and dispatches expiry notifications.';

    public function __construct(private readonly NotificationRouter $notificationRouter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxWarningDays = max(config('monitoring.expiry_warning_days.allowed', [30, 14, 7, 3, 1]));

        $this->sendSslExpiryWarnings($maxWarningDays);
        $this->sendDomainExpiryWarnings($maxWarningDays);

        return Command::SUCCESS;
    }

    private function sendSslExpiryWarnings(int $maxWarningDays): void
    {
        $sslResults = MonitoringSslResult::query()
            ->where(function ($builder) use ($maxWarningDays): void {
                $builder->where(function ($builder) use ($maxWarningDays): void {
                    $builder->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays($maxWarningDays));
                })
                    ->orWhere('is_valid', false);
            })
            ->with(['monitoring.user'])
            ->get();

        foreach ($sslResults as $sslResult) {
            $this->sendExpiryWarning(
                result: $sslResult,
                notificationType: NotificationType::SSL_EXPIRY,
                expiredEventType: NotificationEventType::SSL_EXPIRED,
                expiringEventType: NotificationEventType::SSL_EXPIRING,
                expiredMessage: 'SSL_EXPIRED',
                expiringMessage: 'SSL_EXPIRING',
                expiredTitle: 'SSL certificate expired',
                expiringTitle: 'SSL certificate expiring soon',
                subject: 'certificate',
                resultMetaKey: 'ssl_result_id'
            );
        }
    }

    private function sendDomainExpiryWarnings(int $maxWarningDays): void
    {
        $domainResults = MonitoringDomainResult::query()
            ->where(function ($builder) use ($maxWarningDays): void {
                $builder->where(function ($builder) use ($maxWarningDays): void {
                    $builder->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays($maxWarningDays));
                })
                    ->orWhere('is_valid', false);
            })
            ->with(['monitoring.user'])
            ->get();

        foreach ($domainResults as $domainResult) {
            $this->sendExpiryWarning(
                result: $domainResult,
                notificationType: NotificationType::DOMAIN_EXPIRY,
                expiredEventType: NotificationEventType::DOMAIN_EXPIRED,
                expiringEventType: NotificationEventType::DOMAIN_EXPIRING,
                expiredMessage: 'DOMAIN_EXPIRED',
                expiringMessage: 'DOMAIN_EXPIRING',
                expiredTitle: 'Domain expired',
                expiringTitle: 'Domain expiring soon',
                subject: 'domain registration',
                resultMetaKey: 'domain_result_id'
            );
        }
    }

    private function sendExpiryWarning(
        MonitoringSslResult|MonitoringDomainResult $result,
        NotificationType $notificationType,
        NotificationEventType $expiredEventType,
        NotificationEventType $expiringEventType,
        string $expiredMessage,
        string $expiringMessage,
        string $expiredTitle,
        string $expiringTitle,
        string $subject,
        string $resultMetaKey
    ): void {
        $monitoring = $result->monitoring;
        if (! $monitoring) {
            return;
        }

        $user = $monitoring->user;
        if (! $user) {
            return;
        }

        if (! $monitoring->notification_on_failure) {
            return;
        }

        $expiresAt = $result->expires_at;
        $isExpired = ! $result->is_valid || ($expiresAt !== null && $expiresAt->lte(now()));
        $daysUntilExpiry = $expiresAt !== null ? $this->daysUntilExpiry($expiresAt) : null;

        if (! $isExpired) {
            if ($daysUntilExpiry === null || ! in_array($daysUntilExpiry, $user->expiryWarningDays(), true)) {
                return;
            }
        }

        $eventType = $isExpired ? $expiredEventType : $expiringEventType;
        $cacheKey = sprintf(
            'expiry_notification_%s_%s_%s_%s',
            $eventType->value,
            $result->id,
            $daysUntilExpiry ?? 'expired',
            now()->format('Y-m-d')
        );

        if (Cache::has($cacheKey)) {
            return;
        }

        $notification = MonitoringNotification::query()->create([
            'monitoring_id' => $monitoring->id,
            'type' => $notificationType,
            'message' => $isExpired ? $expiredMessage : $expiringMessage,
            'read' => false,
            'sent' => false,
        ]);

        $payload = new NotificationPayload(
            eventType: $eventType,
            title: $isExpired ? $expiredTitle : $expiringTitle,
            message: $this->expiryMessage($monitoring->name, $monitoring->target, $subject, $expiresAt, $daysUntilExpiry, $isExpired),
            severity: $isExpired ? 'critical' : 'warning',
            monitoringId: $monitoring->id,
            monitoringName: $monitoring->name,
            monitoringTarget: $monitoring->target,
            occurredAt: now(),
            meta: [
                $resultMetaKey => $result->id,
                'notification_id' => $notification->id,
                'expires_at' => $expiresAt?->toIso8601String(),
                'days_until_expiry' => $daysUntilExpiry,
            ],
        );

        $this->notificationRouter->dispatch($user, $payload);
        $notification->update(['sent' => true]);
        Cache::put($cacheKey, true, now()->addHours(23));
    }

    private function daysUntilExpiry(CarbonInterface $expiresAt): int
    {
        return (int) now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);
    }

    private function expiryMessage(
        string $monitoringName,
        string $monitoringTarget,
        string $subject,
        ?CarbonInterface $expiresAt,
        ?int $daysUntilExpiry,
        bool $isExpired
    ): string {
        if ($expiresAt === null) {
            return sprintf('%s (%s) %s expiry date is unknown.', $monitoringName, $monitoringTarget, $subject);
        }

        if ($isExpired) {
            return sprintf('%s (%s) %s expired at %s.', $monitoringName, $monitoringTarget, $subject, $expiresAt->toDateTimeString());
        }

        return sprintf(
            '%s (%s) %s expires in %d days at %s.',
            $monitoringName,
            $monitoringTarget,
            $subject,
            $daysUntilExpiry,
            $expiresAt->toDateTimeString()
        );
    }
}
