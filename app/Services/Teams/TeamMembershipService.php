<?php

declare(strict_types=1);

namespace App\Services\Teams;

use App\Enums\TeamRole;
use App\Models\Monitoring;
use App\Models\MonitoringNotificationPreference;
use App\Models\MonitoringNotificationState;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamMembershipService
{
    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function createTeam(User $user, array $data): Team
    {
        return DB::transaction(function () use ($user, $data): Team {
            $team = Team::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_by_user_id' => $user->id,
            ]);

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::ADMIN,
            ]);

            return $team;
        });
    }

    public function changeRole(TeamMembership $membership, TeamRole $role): void
    {
        if ($membership->role === $role) {
            return;
        }

        if ($membership->role === TeamRole::ADMIN && $role !== TeamRole::ADMIN) {
            $this->assertNotLastAdmin($membership);
        }

        $membership->update(['role' => $role]);
    }

    public function remove(TeamMembership $membership): void
    {
        if ($membership->role === TeamRole::ADMIN) {
            $this->assertNotLastAdmin($membership);
        }

        $this->deleteMemberMonitoringState($membership);
        $membership->delete();
    }

    public function leave(Team $team, User $user): void
    {
        /** @var TeamMembership|null $membership */
        $membership = $team->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return;
        }

        $this->remove($membership);
    }

    public function assertAdmin(Team $team, User $user): void
    {
        if (! $team->isMember($user)) {
            abort(404);
        }

        if (! $team->isAdmin($user)) {
            abort(403);
        }
    }

    public function assertMember(Team $team, User $user): void
    {
        if (! $team->isMember($user)) {
            abort(404);
        }
    }

    private function assertNotLastAdmin(TeamMembership $membership): void
    {
        if (! $membership->team) {
            $membership->load('team');
        }

        $adminCount = $membership->team->adminCount();

        if ($adminCount <= 1) {
            throw ValidationException::withMessages([
                'role' => __('team.validation.last_admin'),
            ]);
        }
    }

    private function deleteMemberMonitoringState(TeamMembership $membership): void
    {
        $monitoringIds = Monitoring::query()
            ->withoutGlobalScopes()
            ->where('team_id', $membership->team_id)
            ->pluck('id');

        if ($monitoringIds->isEmpty()) {
            return;
        }

        MonitoringNotificationPreference::query()
            ->where('user_id', $membership->user_id)
            ->whereIn('monitoring_id', $monitoringIds)
            ->delete();

        MonitoringNotificationState::query()
            ->where('user_id', $membership->user_id)
            ->whereHas('monitoringNotification', function ($builder) use ($monitoringIds): void {
                $builder->whereIn('monitoring_id', $monitoringIds);
            })
            ->delete();
    }
}
