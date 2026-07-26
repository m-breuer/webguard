<?php

declare(strict_types=1);

namespace App\Http\Resources\External;

use App\Models\MobilePushDevice;
use Illuminate\Http\Request;

final class MobilePushDeviceResource extends CompatibilityResource
{
    /**
     * @return array<string, bool|string|null>
     */
    public function toArray(Request $request): array
    {
        /** @var MobilePushDevice $mobilePushDevice */
        $mobilePushDevice = $this->resource;

        return [
            'id' => $mobilePushDevice->id,
            'platform' => $mobilePushDevice->platform,
            'push_provider' => $mobilePushDevice->push_provider,
            'device_name' => $mobilePushDevice->device_name,
            'app_version' => $mobilePushDevice->app_version,
            'locale' => $mobilePushDevice->locale,
            'timezone' => $mobilePushDevice->timezone,
            'enabled' => $mobilePushDevice->enabled,
            'notifications_authorized_at' => $mobilePushDevice->notifications_authorized_at?->toIso8601String(),
            'last_registered_at' => $mobilePushDevice->last_registered_at?->toIso8601String(),
            'last_seen_at' => $mobilePushDevice->last_seen_at?->toIso8601String(),
            'revoked_at' => $mobilePushDevice->revoked_at?->toIso8601String(),
            'created_at' => $mobilePushDevice->created_at?->toIso8601String(),
            'updated_at' => $mobilePushDevice->updated_at?->toIso8601String(),
        ];
    }
}
