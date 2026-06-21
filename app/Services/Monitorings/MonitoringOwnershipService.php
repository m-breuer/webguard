<?php

declare(strict_types=1);

namespace App\Services\Monitorings;

use App\Models\Monitoring;
use App\Models\Team;
use App\Models\User;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\DB;

class MonitoringOwnershipService
{
    public function moveToTeam(Monitoring $monitoring, Team $team, User $user): Monitoring
    {
        abort_unless($team->isAdmin($user), 403);
        abort_unless($monitoring->isManageableBy($user), 403);

        return DB::transaction(function () use ($monitoring, $team, $user): Monitoring {
            $monitoring->groups()->detach();
            $monitoring->update([
                'user_id' => null,
                'team_id' => $team->id,
                'created_by_user_id' => $monitoring->created_by_user_id ?? $user->id,
            ]);

            $this->flushStatsCache($monitoring);

            return $monitoring->refresh();
        });
    }

    public function moveToPrivate(Monitoring $monitoring, User $user): Monitoring
    {
        abort_unless($monitoring->isTeamOwned(), 404);
        abort_unless($monitoring->isManageableBy($user), 403);

        return DB::transaction(function () use ($monitoring, $user): Monitoring {
            $monitoring->update([
                'user_id' => $user->id,
                'team_id' => null,
                'created_by_user_id' => $monitoring->created_by_user_id ?? $user->id,
            ]);

            $this->flushStatsCache($monitoring);

            return $monitoring->refresh();
        });
    }

    private function flushStatsCache(Monitoring $monitoring): void
    {
        if (cache()->getStore() instanceof TaggableStore) {
            cache()->tags(['monitoring:' . $monitoring->id])->flush();
        }
    }
}
