<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMobilePushDeviceRequest;
use App\Http\Requests\Api\UpdateMobilePushDeviceRequest;
use App\Http\Resources\External\MobilePushDeviceResource;
use App\Models\MobilePushDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePushDeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $user->mobilePushDevices()
                ->latest('last_registered_at')
                ->latest()
                ->orderByDesc('id')
                ->get()
                ->map(fn (MobilePushDevice $mobilePushDevice): array => MobilePushDeviceResource::make($mobilePushDevice)->resolve($request))
                ->values(),
        ]);
    }

    public function store(StoreMobilePushDeviceRequest $storeMobilePushDeviceRequest): JsonResponse
    {
        /** @var User $user */
        $user = $storeMobilePushDeviceRequest->user();
        $validated = $storeMobilePushDeviceRequest->validated();
        $pushToken = (string) $validated['push_token'];
        $pushProvider = (string) ($validated['push_provider'] ?? 'fcm');
        $tokenHash = hash('sha256', $pushToken);

        $mobilePushDevice = MobilePushDevice::query()->firstOrNew([
            'push_provider' => $pushProvider,
            'token_hash' => $tokenHash,
        ]);
        $wasRecentlyCreated = ! $mobilePushDevice->exists;

        $mobilePushDevice->fill([
            'user_id' => $user->id,
            'platform' => $validated['platform'],
            'push_provider' => $pushProvider,
            'push_token' => $pushToken,
            'token_hash' => $tokenHash,
            'device_name' => $validated['device_name'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'locale' => $validated['locale'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
            'enabled' => (bool) ($validated['enabled'] ?? true),
            'notifications_authorized_at' => $validated['notifications_authorized_at'] ?? now(),
            'last_registered_at' => now(),
            'last_seen_at' => now(),
            'revoked_at' => null,
        ])->save();

        if ($mobilePushDevice->enabled) {
            $this->enableMobilePushChannel($user);
        }

        return response()->json([
            'data' => MobilePushDeviceResource::make($mobilePushDevice)->resolve($storeMobilePushDeviceRequest),
        ], $wasRecentlyCreated ? 201 : 200);
    }

    public function update(UpdateMobilePushDeviceRequest $updateMobilePushDeviceRequest, MobilePushDevice $mobilePushDevice): JsonResponse
    {
        /** @var User $user */
        $user = $updateMobilePushDeviceRequest->user();

        abort_unless($mobilePushDevice->user_id === $user->id, 404);

        $validated = $updateMobilePushDeviceRequest->validated();

        if (array_key_exists('enabled', $validated) && (bool) $validated['enabled']) {
            $validated['revoked_at'] = null;
        }

        $mobilePushDevice->fill($validated)->save();

        if ($mobilePushDevice->enabled) {
            $this->enableMobilePushChannel($user);
        }

        return response()->json([
            'data' => MobilePushDeviceResource::make($mobilePushDevice)->resolve($updateMobilePushDeviceRequest),
        ]);
    }

    public function destroy(Request $request, MobilePushDevice $mobilePushDevice): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($mobilePushDevice->user_id === $user->id, 404);

        $mobilePushDevice->forceFill([
            'enabled' => false,
            'revoked_at' => now(),
        ])->save();

        $this->disableMobilePushChannelWhenNoActiveDevicesRemain($user);

        return response()->json(null, 204);
    }

    private function enableMobilePushChannel(User $user): void
    {
        $channels = is_array($user->notification_channels) ? $user->notification_channels : [];
        $channels[NotificationChannel::MOBILE_PUSH->value] = ['enabled' => true];

        $user->forceFill(['notification_channels' => $channels])->save();
    }

    private function disableMobilePushChannelWhenNoActiveDevicesRemain(User $user): void
    {
        if ($user->mobilePushDevices()->active()->exists()) {
            return;
        }

        $channels = is_array($user->notification_channels) ? $user->notification_channels : [];
        $channels[NotificationChannel::MOBILE_PUSH->value] = ['enabled' => false];

        $user->forceFill(['notification_channels' => $channels])->save();
    }
}
