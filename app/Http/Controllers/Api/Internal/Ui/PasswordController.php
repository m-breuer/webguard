<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

final class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $updatePasswordRequest): JsonResponse
    {
        /** @var User $user */
        $user = $updatePasswordRequest->user();
        $validated = $updatePasswordRequest->validated();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'data' => [
                'updated' => true,
            ],
        ]);
    }
}
