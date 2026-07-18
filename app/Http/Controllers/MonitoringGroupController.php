<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StatusPageComponentSource;
use App\Http\Requests\MonitoringGroupRequest;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\User;
use App\Services\Monitorings\MonitoringGroupAssignmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringGroupController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $modalForm = request()->string('modal')->toString();
        $modalMonitoringGroup = null;
        $modalMonitorings = collect();

        if ($modalForm === 'monitoring-group-create') {
            $modalMonitorings = $this->assignableMonitorings($user);
        } elseif ($modalForm === 'monitoring-group-edit' && request()->filled('monitoring_group')) {
            $modalMonitoringGroup = MonitoringGroup::query()->findOrFail(request()->string('monitoring_group')->toString());
            $this->authorizeOwner($modalMonitoringGroup);
            $modalMonitoringGroup->load([
                'monitorings' => fn ($query) => $query->privateOwnedBy($user),
            ]);
            $modalMonitorings = $this->assignableMonitorings($user);
        }

        return view('monitoring-groups.index', [
            'monitoringGroups' => $user->monitoringGroups()
                ->withCount('monitorings')
                ->orderBy('name')
                ->paginate(10),
            'modalForm' => $modalForm,
            'modalMonitoringGroup' => $modalMonitoringGroup,
            'modalMonitorings' => $modalMonitorings,
        ]);
    }

    public function create(): View
    {
        abort_if(Auth::user()->isDemo(), 403);

        /** @var User $user */
        $user = Auth::user();

        return view('monitoring-groups.create', [
            'monitorings' => $this->assignableMonitorings($user),
        ]);
    }

    public function store(
        MonitoringGroupRequest $monitoringGroupRequest,
        MonitoringGroupAssignmentService $monitoringGroupAssignmentService
    ): RedirectResponse {
        abort_if(Auth::user()->isDemo(), 403);

        /** @var User $user */
        $user = $monitoringGroupRequest->user();
        $validated = $monitoringGroupRequest->validated();
        $monitoringIds = $validated['monitoring_ids'] ?? [];
        unset($validated['monitoring_ids']);

        DB::transaction(function () use ($user, $validated, $monitoringIds, $monitoringGroupAssignmentService): void {
            $monitoringGroup = $user->monitoringGroups()->create($validated);
            $monitoringGroupAssignmentService->syncAssignableMonitorings($monitoringGroup, $user, $monitoringIds);
        });

        return to_route('monitoring-groups.index')
            ->with('success', __('monitoring_group.messages.created'));
    }

    public function edit(MonitoringGroup $monitoringGroup): View
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($monitoringGroup);

        /** @var User $user */
        $user = Auth::user();
        $monitoringGroup->load([
            'monitorings' => fn ($query) => $query->privateOwnedBy($user),
        ]);

        return view('monitoring-groups.edit', [
            'monitoringGroup' => $monitoringGroup,
            'monitorings' => $this->assignableMonitorings($user),
        ]);
    }

    public function update(
        MonitoringGroupRequest $monitoringGroupRequest,
        MonitoringGroup $monitoringGroup,
        MonitoringGroupAssignmentService $monitoringGroupAssignmentService
    ): RedirectResponse {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($monitoringGroup);

        /** @var User $user */
        $user = $monitoringGroupRequest->user();
        $validated = $monitoringGroupRequest->validated();
        $monitoringIds = $validated['monitoring_ids'] ?? [];
        unset($validated['monitoring_ids']);

        DB::transaction(function () use ($monitoringGroup, $validated, $monitoringIds, $user, $monitoringGroupAssignmentService): void {
            $monitoringGroup->update($validated);
            $monitoringGroupAssignmentService->syncAssignableMonitorings($monitoringGroup, $user, $monitoringIds);
        });

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

    public function publishStatusPage(MonitoringGroup $monitoringGroup): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($monitoringGroup);

        /** @var User $user */
        $user = Auth::user();

        $statusPage = $user->statusPages()->create([
            'name' => $monitoringGroup->name,
            'description' => $monitoringGroup->description,
            'is_public' => true,
        ]);

        $statusPage->components()->create([
            'monitoring_group_id' => $monitoringGroup->id,
            'name' => $monitoringGroup->name,
            'description' => $monitoringGroup->description,
            'position' => 0,
            'source_type' => StatusPageComponentSource::MONITORING_GROUP,
        ]);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('monitoring_group.messages.status_page_created'));
    }

    private function authorizeOwner(MonitoringGroup $monitoringGroup): void
    {
        abort_unless($monitoringGroup->user_id === Auth::id(), 404);
    }

    /**
     * @return Collection<int, Monitoring>
     */
    private function assignableMonitorings(User $user): Collection
    {
        return Monitoring::query()
            ->privateOwnedBy($user)
            ->orderBy('name')
            ->get(['id', 'name', 'target']);
    }
}
