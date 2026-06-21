<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\User;
use App\Services\Notifications\MonitoringNotificationPreferenceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonitoringNotificationPreferenceController extends Controller
{
    public function update(
        Request $request,
        Monitoring $monitoring,
        MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        abort_unless($monitoring->isVisibleTo($user), 404);

        $validated = $request->validate([
            'notification_on_failure' => ['nullable', 'boolean'],
            'notification_channels' => ['nullable', 'array'],
            'notification_channels.*' => [
                'string',
                Rule::in($user->enabledNotificationChannelKeys()),
            ],
            'ssl_expiry_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $monitoringNotificationPreference = $monitoringNotificationPreferenceResolver->preferenceFor($monitoring, $user);
        $monitoringNotificationPreference->update([
            'notification_on_failure' => $request->boolean('notification_on_failure'),
            'notification_channels' => array_values(array_unique($validated['notification_channels'] ?? [])),
            'ssl_expiry_warning_days' => (int) $validated['ssl_expiry_warning_days'],
        ]);

        if ($monitoring->user_id === $user->id && $monitoring->team_id === null) {
            $monitoring->update([
                'notification_on_failure' => $monitoringNotificationPreference->notification_on_failure,
                'notification_channels' => $monitoringNotificationPreference->notification_channels,
                'ssl_expiry_warning_days' => $monitoringNotificationPreference->ssl_expiry_warning_days,
            ]);
        }

        return back()->with('success', __('monitoring.messages.notification_preferences_updated'));
    }
}
