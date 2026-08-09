<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\User;
use App\Services\Notifications\MonitoringNotificationPreferenceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileMonitoringNotificationPreferenceController extends Controller
{
    public function show(Request $request, string $monitoring, MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $monitoringModel = Monitoring::query()->visibleTo($user)->whereKey($monitoring)->firstOrFail();

        return response()->json(['data' => $this->payload($monitoringModel, $user, $monitoringNotificationPreferenceResolver)]);
    }

    public function update(Request $request, string $monitoring, MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        $monitoringModel = Monitoring::query()->visibleTo($user)->whereKey($monitoring)->firstOrFail();
        $validated = $request->validate([
            'notification_on_failure' => ['required', 'boolean'],
            'notification_channels' => ['required', 'array'],
            'notification_channels.*' => ['string', Rule::in($user->enabledNotificationChannelKeys())],
            'ssl_expiry_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);
        $monitoringNotificationPreference = $monitoringNotificationPreferenceResolver->preferenceFor($monitoringModel, $user);
        $monitoringNotificationPreference->update([
            'notification_on_failure' => (bool) $validated['notification_on_failure'],
            'notification_channels' => array_values(array_unique($validated['notification_channels'])),
            'ssl_expiry_warning_days' => (int) $validated['ssl_expiry_warning_days'],
        ]);

        if ($monitoringModel->user_id === $user->id && $monitoringModel->team_id === null) {
            $monitoringModel->update([
                'notification_on_failure' => $monitoringNotificationPreference->notification_on_failure,
                'notification_channels' => $monitoringNotificationPreference->notification_channels,
                'ssl_expiry_warning_days' => $monitoringNotificationPreference->ssl_expiry_warning_days,
            ]);
        }

        return response()->json(['data' => $this->payload($monitoringModel->refresh(), $user, $monitoringNotificationPreferenceResolver)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Monitoring $monitoring, User $user, MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver): array
    {
        $monitoringNotificationPreference = $monitoringNotificationPreferenceResolver->preferenceFor($monitoring, $user);

        return [
            'monitoring_id' => $monitoring->id,
            'effective' => [
                'notification_on_failure' => $monitoringNotificationPreference->notification_on_failure,
                'notification_channels' => $monitoringNotificationPreference->notification_channels,
                'ssl_expiry_warning_days' => $monitoringNotificationPreference->ssl_expiry_warning_days,
            ],
            'source' => $monitoring->user_id === $user->id && $monitoring->team_id === null ? 'private_default' : 'team_member',
            'permitted_channels' => $user->enabledNotificationChannelKeys(),
            'can_update' => ! $user->isDemo(),
            'updated_at' => $monitoringNotificationPreference->updated_at?->toIso8601String(),
        ];
    }
}
