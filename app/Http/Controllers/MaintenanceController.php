<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('maintenance.index', [
            'monitorings' => $user->monitorings()
                ->with('groups:id,name')
                ->orderBy('name')
                ->get(),
            'monitoringGroups' => $user->monitoringGroups()
                ->withCount('monitorings')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_if($maintenanceRequest->user()?->isDemo(), 403);

        $validated = $maintenanceRequest->validated();

        $updatedCount = $this->targetMonitorings($maintenanceRequest)
            ->update([
                'maintenance_from' => Date::parse($validated['maintenance_from']),
                'maintenance_until' => isset($validated['maintenance_until'])
                    ? Date::parse($validated['maintenance_until'])
                    : null,
            ]);

        return to_route('maintenance.index')
            ->with('success', trans_choice('maintenance.messages.scheduled', $updatedCount, ['count' => $updatedCount]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_if($request->user()?->isDemo(), 403);

        $validated = $request->validate([
            'monitoring_id' => [
                'required',
                'string',
                Rule::exists('monitorings', 'id')->where('user_id', $request->user()?->id),
            ],
        ]);

        Monitoring::query()
            ->whereKey($validated['monitoring_id'])
            ->update([
                'maintenance_from' => null,
                'maintenance_until' => null,
            ]);

        return to_route('maintenance.index')
            ->with('success', __('maintenance.messages.cleared'));
    }

    private function targetMonitorings(MaintenanceRequest $request): Builder
    {
        $query = Monitoring::query();

        if ($request->string('scope')->toString() === 'group') {
            return $query->whereHas('groups', function ($builder) use ($request): void {
                $builder->where('monitoring_groups.id', $request->string('monitoring_group_id')->toString());
            });
        }

        return $query->whereKey($request->string('monitoring_id')->toString());
    }
}
