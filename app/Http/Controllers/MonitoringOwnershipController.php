<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\Team;
use App\Models\User;
use App\Services\Monitorings\MonitoringOwnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonitoringOwnershipController extends Controller
{
    public function moveToTeam(
        Request $request,
        Monitoring $monitoring,
        MonitoringOwnershipService $monitoringOwnershipService
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);

        $validated = $request->validate([
            'team_id' => [
                'required',
                'string',
                Rule::exists('teams', 'id'),
                function ($attribute, $value, $fail) use ($user): void {
                    if (! Team::query()->administeredBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('team.validation.not_admin'));
                    }
                },
            ],
        ]);

        $team = Team::query()->findOrFail($validated['team_id']);
        $monitoringOwnershipService->moveToTeam($monitoring, $team, $user);

        return to_route('monitorings.show', $monitoring)
            ->with('success', __('team.ownership.moved_to_team'));
    }

    public function moveToPrivate(
        Request $request,
        Monitoring $monitoring,
        MonitoringOwnershipService $monitoringOwnershipService
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);

        $monitoringOwnershipService->moveToPrivate($monitoring, $user);

        return to_route('monitorings.show', $monitoring)
            ->with('success', __('team.ownership.moved_to_private'));
    }
}
