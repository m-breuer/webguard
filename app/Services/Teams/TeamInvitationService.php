<?php

declare(strict_types=1);

namespace App\Services\Teams;

use App\Enums\TeamRole;
use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamInvitationService
{
    public function invite(Team $team, User $user, string $email, TeamRole $teamRole): TeamInvitation
    {
        $normalizedEmail = Str::lower(mb_trim($email));

        if ($team->memberships()
            ->whereHas('user', fn ($builder) => $builder->where('email', $normalizedEmail))
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => __('team.validation.already_member'),
            ]);
        }

        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($team, $user, $normalizedEmail, $teamRole, $token): TeamInvitation {
            $team->invitations()
                ->where('email', $normalizedEmail)
                ->whereNull('accepted_at')
                ->delete();

            return $team->invitations()->create([
                'email' => $normalizedEmail,
                'role' => $teamRole,
                'token_hash' => $this->hashToken($token),
                'invited_by_user_id' => $user->id,
                'expires_at' => now()->addDays(7),
            ]);
        });

        $invitation->load('team');

        Mail::to($normalizedEmail)->send(
            (new TeamInvitationMail($invitation, $token))
                ->locale(User::query()->where('email', $normalizedEmail)->value('locale') ?? app()->getLocale())
        );

        return $invitation;
    }

    public function findPendingByToken(string $token): TeamInvitation
    {
        /** @var TeamInvitation|null $invitation */
        $invitation = TeamInvitation::query()
            ->where('token_hash', $this->hashToken($token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('team')
            ->first();

        abort_unless($invitation, 404);

        return $invitation;
    }

    public function accept(string $token, User $user): Team
    {
        return DB::transaction(function () use ($token, $user): Team {
            $teamInvitation = $this->findPendingByToken($token);

            if (Str::lower($user->email) !== Str::lower($teamInvitation->email)) {
                throw ValidationException::withMessages([
                    'email' => __('team.validation.email_mismatch'),
                ]);
            }

            $teamInvitation->team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $teamInvitation->role]
            );

            $teamInvitation->update(['accepted_at' => now()]);

            return $teamInvitation->team;
        });
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
