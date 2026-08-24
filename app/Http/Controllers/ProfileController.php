<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Jobs\DeleteUser;
use App\Models\User;
use App\Services\ApiKeyService;
use App\Services\Notifications\NotificationChannelTestService;
use App\Services\NotificationSettingsService;
use App\Services\UserDeletionPreparationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Throwable;

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
    public function edit(Request $request, ApiKeyService $apiKeyService): View
    {
        $user = $request->user();
        $modalForm = $request->input('modal');
        $isProfileInformationForm = $modalForm === 'profile-information';
        $isPasswordForm = $modalForm === 'profile-password';
        $showNotificationChannelsHint = false;

        if ((! $request->ajax() || $isProfileInformationForm) && $user
            && ! $user->hasEnabledNotificationChannels() && $user->notification_channels_hint_seen_at === null) {
            $user->forceFill([
                'notification_channels_hint_seen_at' => now(),
            ])->save();

            $showNotificationChannelsHint = true;
        }

        if ($request->ajax() && ($isProfileInformationForm || $isPasswordForm)) {
            return view($isProfileInformationForm
                ? 'profile.partials.update-profile-information-form'
                : 'profile.partials.update-password-form', [
                    'user' => $user,
                    'showNotificationChannelsHint' => $showNotificationChannelsHint,
                    'modal' => true,
                ]);
        }

        return view('profile.edit', [
            'user' => $user,
            'apiKeys' => $user instanceof User ? $apiKeyService->paginate($user, 100, null) : null,
            'showNotificationChannelsHint' => $showNotificationChannelsHint,
            'modalForm' => $modalForm,
        ]);
    }

    /**
     * Update the user's profile information and reset email verification if needed.
     *
     * @param  ProfileRequest  $profileRequest  The request containing validated profile data.
     * @return RedirectResponse A redirect response after updating the profile.
     */
    public function update(
        ProfileRequest $profileRequest,
        NotificationSettingsService $notificationSettingsService
    ): RedirectResponse {
        $validated = $profileRequest->validated();
        $user = $profileRequest->user();
        $user->fill(Arr::only($validated, ['name', 'email', 'theme']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $notificationSettingsService->update($user, $profileRequest);

        return to_route('profile.edit')
            ->with('success', __('profile.messages.profile_updated'));
    }

    public function updateTheme(UpdateThemeRequest $updateThemeRequest): RedirectResponse
    {
        $updateThemeRequest->user()->update($updateThemeRequest->validated());

        return back();
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

        activity('user')
            ->causedBy($user)
            ->performedOn($user)
            ->event('delete_requested')
            ->withProperties(['action' => 'account_deletion_requested'])
            ->log('user_delete_requested');

        Auth::logout();

        $userDeletionPreparationService->disableLoginUntilDeletion($user);

        dispatch(new DeleteUser($user));

        $deleteUserRequest->session()->invalidate();
        $deleteUserRequest->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function sendNotificationChannelTest(
        Request $request,
        string $channel,
        NotificationChannelTestService $notificationChannelTestService
    ): RedirectResponse {
        $notificationChannel = NotificationChannel::tryFrom($channel);

        abort_unless($notificationChannel instanceof NotificationChannel, 404);

        /** @var User $user */
        $user = $request->user();
        $config = data_get($user->notification_channels, $notificationChannel->value, []);
        $config = is_array($config) ? $config : [];
        $channelName = __('profile.notification_settings.channels.' . $notificationChannel->value . '.title');
        $errorKey = 'notification_channels.' . $notificationChannel->value;

        try {
            $notificationChannelTestService->send($user, $notificationChannel, $config);
        } catch (Throwable $throwable) {
            Log::warning('Notification channel test failed.', [
                'channel' => $notificationChannel->value,
                'user_id' => $user->id,
                'exception' => $throwable->getMessage(),
            ]);

            return back()->withErrors([
                $errorKey => __('profile.notification_settings.test.messages.failed', ['channel' => $channelName]),
            ]);
        }

        return back()->with('success', __('profile.notification_settings.test.messages.sent', ['channel' => $channelName]));
    }
}
