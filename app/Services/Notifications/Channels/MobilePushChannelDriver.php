<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Models\MobilePushDevice;
use App\Services\Notifications\FcmClient;
use App\Services\Notifications\NotificationPayload;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MobilePushChannelDriver implements NotificationChannelDriver
{
    public function __construct(private readonly FcmClient $fcmClient) {}

    public function channel(): string
    {
        return NotificationChannel::MOBILE_PUSH->value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        $userId = $config['user_id'] ?? null;

        return is_string($userId)
            && $this->fcmClient->isConfigured()
            && MobilePushDevice::query()
                ->where('user_id', $userId)
                ->active()
                ->exists();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function send(NotificationPayload $notificationPayload, array $config): void
    {
        $userId = $config['user_id'] ?? null;

        throw_unless(is_string($userId), RuntimeException::class, 'Mobile push user context is missing.');

        $devices = MobilePushDevice::query()
            ->where('user_id', $userId)
            ->active()
            ->get();

        $sentCount = 0;
        $errors = [];

        foreach ($devices as $device) {
            try {
                $this->fcmClient->sendToToken($device->push_token, $notificationPayload);
                $sentCount++;
                $device->forceFill(['last_seen_at' => now()])->save();
            } catch (Throwable $throwable) {
                $errors[] = $throwable->getMessage();

                if ($this->shouldRevokeDevice($throwable->getMessage())) {
                    $device->forceFill([
                        'enabled' => false,
                        'revoked_at' => now(),
                    ])->save();
                }
            }
        }

        if ($sentCount < 1) {
            throw new RuntimeException($errors[0] ?? 'Mobile push notification did not reach any active device.');
        }

        if ($errors !== []) {
            Log::warning('Mobile push notification partially failed.', [
                'user_id' => $userId,
                'sent_count' => $sentCount,
                'failed_count' => count($errors),
            ]);
        }
    }

    private function shouldRevokeDevice(string $errorMessage): bool
    {
        return str_contains($errorMessage, 'UNREGISTERED')
            || str_contains($errorMessage, 'registration-token-not-registered')
            || str_contains($errorMessage, 'Requested entity was not found');
    }
}
