<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserDeletionPreparationService
{
    public function disableLoginUntilDeletion(User $user): void
    {
        $emailBeforeDeletion = $user->email;

        $user->forceFill([
            'email' => sprintf('deleted+%s@webguard.invalid', Str::lower($user->id)),
            'password' => Str::random(64),
            'remember_token' => null,
            'github_id' => null,
            'github_token' => null,
            'github_refresh_token' => null,
        ])->save();

        $user->tokens()->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $emailBeforeDeletion)->delete();
    }
}
