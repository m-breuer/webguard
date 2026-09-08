<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FrontendTranslationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FrontendTranslationController extends Controller
{
    public function __invoke(Request $request, FrontendTranslationCatalog $frontendTranslationCatalog): JsonResponse
    {
        $locale = $request->query('locale');

        return response()->json([
            'data' => $frontendTranslationCatalog->payload(is_string($locale) ? $locale : null),
        ]);
    }
}
