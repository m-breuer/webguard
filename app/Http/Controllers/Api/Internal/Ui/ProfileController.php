<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileAccountRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class ProfileController extends Controller
{
    public function update(UpdateProfileAccountRequest $updateProfileAccountRequest): JsonResponse
    {
        /** @var User $user */
        $user = $updateProfileAccountRequest->user();
        $user->fill($updateProfileAccountRequest->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'is_verified' => $user->hasVerifiedEmail(),
            ],
        ]);
    }
}
