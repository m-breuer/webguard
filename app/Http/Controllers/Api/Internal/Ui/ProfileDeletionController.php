<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Jobs\DeleteUser;
use App\Models\User;
use App\Services\UserDeletionPreparationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class ProfileDeletionController extends Controller
{
    public function __invoke(
        DeleteUserRequest $deleteUserRequest,
        UserDeletionPreparationService $userDeletionPreparationService
    ): JsonResponse {
        /** @var User $user */
        $user = $deleteUserRequest->user();

        activity('user')
            ->causedBy($user)
            ->performedOn($user)
            ->event('delete_requested')
            ->withProperties(['action' => 'account_deletion_requested'])
            ->log('user_delete_requested');

        Auth::logout();
        $userDeletionPreparationService->disableLoginUntilDeletion($user);
        DeleteUser::dispatch($user);

        $deleteUserRequest->session()->invalidate();
        $deleteUserRequest->session()->regenerateToken();

        return response()->json([
            'data' => ['deletion_scheduled' => true],
        ]);
    }
}
