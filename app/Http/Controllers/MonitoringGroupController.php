<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MonitoringGroupRequest;
use App\Models\MonitoringGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonitoringGroupController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('monitoring-groups.index', [
            'monitoringGroups' => $user->monitoringGroups()
                ->withCount('monitorings')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        abort_if(Auth::user()->isDemo(), 403);

        return view('monitoring-groups.create');
    }

    public function store(MonitoringGroupRequest $monitoringGroupRequest): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);

        /** @var User $user */
        $user = $monitoringGroupRequest->user();
        $user->monitoringGroups()->create($monitoringGroupRequest->validated());

        return to_route('monitoring-groups.index')
            ->with('success', __('monitoring_group.messages.created'));
    }

    public function edit(MonitoringGroup $monitoringGroup): View
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($monitoringGroup);

        return view('monitoring-groups.edit', [
            'monitoringGroup' => $monitoringGroup,
        ]);
    }

    public function update(MonitoringGroupRequest $monitoringGroupRequest, MonitoringGroup $monitoringGroup): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($monitoringGroup);

        $monitoringGroup->update($monitoringGroupRequest->validated());

        return to_route('monitoring-groups.index')
            ->with('success', __('monitoring_group.messages.updated'));
    }

    public function destroy(MonitoringGroup $monitoringGroup): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($monitoringGroup);

        $monitoringGroup->delete();

        return to_route('monitoring-groups.index')
            ->with('success', __('monitoring_group.messages.deleted'));
    }

    private function authorizeOwner(MonitoringGroup $monitoringGroup): void
    {
        abort_unless($monitoringGroup->user_id === Auth::id(), 404);
    }
}
