<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class GuestLoginController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $guest = User::query()->where('role', UserRole::GUEST)->first();

        if (! $guest) {
            return response()->json(['error' => __('auth.guest_login.no_guest_user_found')], 404);
        }

        return response()->json(['email' => $guest->email]);
    }
}
