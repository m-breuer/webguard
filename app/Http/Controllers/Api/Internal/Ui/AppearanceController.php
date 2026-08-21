<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class AppearanceController extends Controller
{
    public function __invoke(UpdateThemeRequest $updateThemeRequest): JsonResponse
    {
        /** @var User $user */
        $user = $updateThemeRequest->user();
        $user->update($updateThemeRequest->validated());

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'theme' => $user->refresh()->theme,
            ],
        ]);
    }
}
