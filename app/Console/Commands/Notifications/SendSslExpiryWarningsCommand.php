<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\NotificationEventType;
use App\Enums\NotificationType;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringSslResult;
use App\Services\Notifications\MonitoringNotificationPreferenceResolver;
use App\Services\Notifications\MonitoringNotificationStateService;
use App\Services\Notifications\NotificationPayload;
use App\Services\Notifications\NotificationRouter;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Checks SSL certificates and domains and dispatches expiry notifications.')]
#[Signature('notifications:send-ssl-expiry-warnings')]
class SendSslExpiryWarningsCommand extends Command
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
        $this->sendSslExpiryWarnings();
        $this->sendDomainExpiryWarnings();

        return Command::SUCCESS;
    }

    private function sendSslExpiryWarnings(): void
    {
        $sslResults = MonitoringSslResult::query()
            ->where(function ($builder): void {
                $builder->where(function ($builder): void {
                    $builder->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays(365));
                })
                    ->orWhere('is_valid', false);
            })
            ->with(['monitoring.user', 'monitoring.team.users'])
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
                resultMetaKey: 'ssl_result_id',
                warningWindowDays: $sslResult->monitoring?->ssl_expiry_warning_days ?? 7
            );
        }
    }

    private function sendDomainExpiryWarnings(): void
    {
        $domainResults = MonitoringDomainResult::query()
            ->where(function ($builder): void {
                $builder->where(function ($builder): void {
                    $builder->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays(365));
                })
                    ->orWhere('is_valid', false);
            })
            ->with(['monitoring.user', 'monitoring.team.users'])
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
                resultMetaKey: 'domain_result_id',
                warningWindowDays: $domainResult->monitoring?->ssl_expiry_warning_days ?? 7
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
        string $resultMetaKey,
        ?int $warningWindowDays = null
    ): void {
        $monitoring = $result->monitoring;
        if (! $monitoring) {
            return;
        }

        $expiresAt = $result->expires_at;
        $isExpired = ! $result->is_valid || ($expiresAt !== null && $expiresAt->lte(now()));
        $daysUntilExpiry = $expiresAt !== null ? $this->daysUntilExpiry($expiresAt) : null;

        $recipients = $this->monitoringNotificationPreferenceResolver
            ->recipientsFor($monitoring)
            ->filter(function (array $recipient) use ($daysUntilExpiry, $isExpired): bool {
                $preference = $recipient['preference'];

                if (! $preference->notification_on_failure) {
                    return false;
                }

                return $isExpired || $this->shouldWarn($daysUntilExpiry, $preference->ssl_expiry_warning_days);
            })
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $eventType = $isExpired ? $expiredEventType : $expiringEventType;
        $monitoringNotification = MonitoringNotification::query()->firstOrCreate([
            'expiry_notification_key' => $this->expiryNotificationKey($result, $eventType, $expiresAt, $isExpired),
        ], [
            'monitoring_id' => $monitoring->id,
            'type' => $notificationType,
            'message' => $isExpired ? $expiredMessage : $expiringMessage,
            'read' => false,
            'sent' => false,
        ]);

        if (! $monitoringNotification->wasRecentlyCreated) {
            return;
        }

        $notificationPayload = new NotificationPayload(
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
                'notification_id' => $monitoringNotification->id,
                'expires_at' => $expiresAt?->toIso8601String(),
                'days_until_expiry' => $daysUntilExpiry,
            ],
        );

        $recipients->each(function (array $recipient) use ($monitoringNotification, $notificationPayload): void {
            $user = $recipient['user'];
            $preference = $recipient['preference'];

            $this->monitoringNotificationStateService->ensureState($monitoringNotification, $user);
            $this->notificationRouter->dispatch($user, $notificationPayload, $preference->notification_channels);
            $this->monitoringNotificationStateService->markSent($monitoringNotification, $user);
        });

        $monitoringNotification->update(['sent' => true]);
    }

    private function expiryNotificationKey(
        MonitoringSslResult|MonitoringDomainResult $result,
        NotificationEventType $eventType,
        ?CarbonInterface $expiresAt,
        bool $isExpired
    ): string {
        return hash('sha256', implode('|', [
            $eventType->value,
            $result::class,
            $result->id,
            $expiresAt?->toIso8601String() ?? 'unknown-expiry',
            $isExpired ? 'expired' : 'expiring',
        ]));
    }

    private function shouldWarn(?int $daysUntilExpiry, ?int $warningWindowDays): bool
    {
        if ($daysUntilExpiry === null || $daysUntilExpiry < 0) {
            return false;
        }

        return $warningWindowDays !== null && $daysUntilExpiry <= $warningWindowDays;
    }

    private function daysUntilExpiry(CarbonInterface $expiresAt): int
    {
        return (int) today()->diffInDays($expiresAt->copy()->startOfDay(), false);
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
