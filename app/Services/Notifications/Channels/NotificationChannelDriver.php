<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Services\Notifications\NotificationPayload;

interface NotificationChannelDriver
{
    public function channel(): string;

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool;

    /**
     * @param  array<string, mixed>  $config
     */
    public function send(NotificationPayload $payload, array $config): void;
}

