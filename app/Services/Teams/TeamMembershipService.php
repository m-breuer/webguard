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

    public function changeRole(TeamMembership $teamMembership, TeamRole $teamRole): void
    {
        if ($teamMembership->role === $teamRole) {
            return;
        }

        if ($teamMembership->role === TeamRole::ADMIN && $teamRole !== TeamRole::ADMIN) {
            $this->assertNotLastAdmin($teamMembership);
        }

        $teamMembership->update(['role' => $teamRole]);
    }

    public function remove(TeamMembership $teamMembership): void
    {
        if ($teamMembership->role === TeamRole::ADMIN) {
            $this->assertNotLastAdmin($teamMembership);
        }

        $this->deleteMemberMonitoringState($teamMembership);
        $teamMembership->delete();
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
        abort_unless($team->isMember($user), 404);

        abort_unless($team->isAdmin($user), 403);
    }

    public function assertMember(Team $team, User $user): void
    {
        abort_unless($team->isMember($user), 404);
    }

    private function assertNotLastAdmin(TeamMembership $teamMembership): void
    {
        $adminCount = TeamMembership::query()
            ->where('team_id', $teamMembership->team_id)
            ->where('role', TeamRole::ADMIN)
            ->count();

        if ($adminCount <= 1) {
            throw ValidationException::withMessages([
                'role' => __('team.validation.last_admin'),
            ]);
        }
    }

    private function deleteMemberMonitoringState(TeamMembership $teamMembership): void
    {
        $monitoringIds = Monitoring::query()
            ->withoutGlobalScopes()
            ->where('team_id', $teamMembership->team_id)
            ->pluck('id');

        if ($monitoringIds->isEmpty()) {
            return;
        }

        MonitoringNotificationPreference::query()
            ->where('user_id', $teamMembership->user_id)
            ->whereIn('monitoring_id', $monitoringIds)
            ->delete();

        MonitoringNotificationState::query()
            ->where('user_id', $teamMembership->user_id)
            ->whereHas('monitoringNotification', function ($builder) use ($monitoringIds): void {
                $builder->whereIn('monitoring_id', $monitoringIds);
            })
            ->delete();
    }
}
