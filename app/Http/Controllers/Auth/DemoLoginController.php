<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DemoLoginController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $demoUser = User::query()->where('role', UserRole::DEMO)->first();

        if (! $demoUser) {
            return response()->json(['error' => __('auth.guest_login.no_guest_user_found')], 404);
        }

        return response()->json(['email' => $demoUser->email]);
    }
}
