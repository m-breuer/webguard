<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        return view('maintenance.index');
    }

    public function store(MaintenanceRequest $maintenanceRequest): RedirectResponse|JsonResponse
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

        $message = trans_choice('maintenance.messages.scheduled', $updatedCount, ['count' => $updatedCount]);

        if ($maintenanceRequest->expectsJson()) {
            return response()->json([
                'message' => $message,
                'updated_count' => $updatedCount,
            ]);
        }

        return to_route('maintenance.index')->with('success', $message);
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        abort_if($request->user()?->isDemo(), 403);
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'monitoring_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($user): void {
                    if (! Monitoring::query()->manageableBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => __('maintenance.form.monitoring')]));
                    }
                },
            ],
        ]);

        Monitoring::query()
            ->manageableBy($user)
            ->whereKey($validated['monitoring_id'])
            ->update([
                'maintenance_from' => null,
                'maintenance_until' => null,
            ]);

        $message = __('maintenance.messages.cleared');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return to_route('maintenance.index')->with('success', $message);
    }

    private function targetMonitorings(MaintenanceRequest $maintenanceRequest): Builder
    {
        /** @var User $user */
        $user = $maintenanceRequest->user();
        $query = Monitoring::query()->manageableBy($user);

        if ($maintenanceRequest->string('scope')->toString() === 'group') {
            return $query->whereHas('groups', function ($builder) use ($maintenanceRequest): void {
                $builder->where('monitoring_groups.id', $maintenanceRequest->string('monitoring_group_id')->toString());
            });
        }

        return $query->whereKey($maintenanceRequest->string('monitoring_id')->toString());
    }
}
