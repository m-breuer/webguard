<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\ProfileRequest;
use App\Jobs\DeleteUser;
use App\Models\User;
use App\Services\UserDeletionPreparationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Class ProfileController
 *
 * Handles profile management including editing, updating, and deleting the authenticated user's account.
 */
class ProfileController extends Controller
{
    /**
     * Display the form to edit the user's profile.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View The view for editing the user's profile.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $showNotificationChannelsHint = false;

        if ($user && ! $user->hasEnabledNotificationChannels() && $user->notification_channels_hint_seen_at === null) {
            $user->forceFill([
                'notification_channels_hint_seen_at' => now(),
            ])->save();

            $showNotificationChannelsHint = true;
        }

        return view('profile.edit', [
            'user' => $user,
            'token' => $user?->currentAccessToken(),
            'showNotificationChannelsHint' => $showNotificationChannelsHint,
        ]);
    }

    /**
     * Update the user's profile information and reset email verification if needed.
     *
     * @param  ProfileRequest  $profileRequest  The request containing validated profile data.
     * @return RedirectResponse A redirect response after updating the profile.
     */
    public function update(ProfileRequest $profileRequest): RedirectResponse
    {
        $validated = $profileRequest->validated();
        $user = $profileRequest->user();
        $user->fill(Arr::only($validated, ['name', 'email', 'theme']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->notification_channels = $this->normalizeNotificationChannels($profileRequest);
        $user->save();

        return to_route('profile.edit')
            ->with('success', __('profile.messages.profile_updated'));
    }

    /**
     * Delete the authenticated user's account after verifying their password.
     *
     * This method logs the user out first, immediately invalidates all login paths,
     * then dispatches the same queued deletion flow used by the admin panel.
     *
     * @param  DeleteUserRequest  $deleteUserRequest  The incoming HTTP request containing the password confirmation.
     * @return RedirectResponse Redirects to the home page after account deletion.
     */
    public function destroy(
        DeleteUserRequest $deleteUserRequest,
        UserDeletionPreparationService $userDeletionPreparationService
    ): RedirectResponse {
        $user = $deleteUserRequest->user();

        if (! $user instanceof User) {
            return Redirect::to('/');
        }

        Auth::logout();

        $userDeletionPreparationService->disableLoginUntilDeletion($user);

        dispatch(new DeleteUser($user));

        $deleteUserRequest->session()->invalidate();
        $deleteUserRequest->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Generate a new API token.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return RedirectResponse A redirect response after generating the token.
     */
    public function apiGenerateToken(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->tokens()->delete();

        $user->createToken('api-access');

        return to_route('profile.edit', ['#api-token']);
    }

    /**
     * Revoke the API token.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return RedirectResponse A redirect response after revoking the token.
     */
    public function apiRevokeToken(Request $request): RedirectResponse
    {
        $request->user()->tokens()->delete();

        return back()->with('success', __('api.configuration.messages.tokens_deleted'));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function normalizeNotificationChannels(ProfileRequest $profileRequest): array
    {
        $eventTypes = NotificationEventType::values();
        $normalized = [];

        foreach (NotificationChannel::values() as $channel) {
            $events = [];

            foreach ($eventTypes as $eventType) {
                $events[$eventType] = $profileRequest->boolean(sprintf('notification_channels.%s.events.%s', $channel, $eventType));
            }

            $channelConfig = [
                'enabled' => $profileRequest->boolean(sprintf('notification_channels.%s.enabled', $channel)),
                'events' => $events,
            ];

            if ($channel === NotificationChannel::SLACK->value || $channel === NotificationChannel::DISCORD->value) {
                $channelConfig['webhook_url'] = mb_trim((string) $profileRequest->input(sprintf('notification_channels.%s.webhook_url', $channel)));
            }

            if ($channel === NotificationChannel::WEBHOOK->value) {
                $channelConfig['url'] = mb_trim((string) $profileRequest->input('notification_channels.webhook.url'));
            }

            if ($channel === NotificationChannel::TELEGRAM->value) {
                $channelConfig['bot_token'] = mb_trim((string) $profileRequest->input('notification_channels.telegram.bot_token'));
                $channelConfig['chat_id'] = mb_trim((string) $profileRequest->input('notification_channels.telegram.chat_id'));
            }

            $normalized[$channel] = $channelConfig;
        }

        return $normalized;
    }
}
