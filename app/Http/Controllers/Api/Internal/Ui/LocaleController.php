<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Enums\SupportedLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLocaleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $updateLocaleRequest): JsonResponse
    {
        /** @var User $user */
        $user = $updateLocaleRequest->user();
        $locale = (string) $updateLocaleRequest->validated('locale');

        $user->update(['locale' => $locale]);

        return response()
            ->json([
                'data' => [
                    'locale' => $locale,
                ],
            ])
            ->withCookie(cookie(
                SupportedLanguage::cookieName(),
                $locale,
                SupportedLanguage::cookieDurationMinutes()
            ));
    }
}
