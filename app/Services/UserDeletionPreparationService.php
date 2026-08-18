<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserDeletionPreparationService
{
    public function disableLoginUntilDeletion(User $user): void
    {
        $this->assertUserIsNotLastTeamAdmin($user);

        $emailBeforeDeletion = $user->email;

        $user->forceFill([
            'email' => sprintf('deleted+%s@webguard.invalid', Str::lower($user->id)),
            'password' => Str::random(64),
            'remember_token' => null,
        ])->save();

        $user->tokens()->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $emailBeforeDeletion)->delete();
    }

    private function assertUserIsNotLastTeamAdmin(User $user): void
    {
        $lastAdminTeam = $user->teamMemberships()
            ->where('role', TeamRole::ADMIN)
            ->whereDoesntHave('team.memberships', function ($builder) use ($user): void {
                $builder->where('role', TeamRole::ADMIN)
                    ->where('user_id', '!=', $user->id);
            })
            ->with('team:id,name')
            ->first();

        if (! $lastAdminTeam) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => __('team.validation.delete_last_admin', ['team' => $lastAdminTeam->team?->name]),
        ]);
    }
}
