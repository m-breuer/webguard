<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Models\MaintenanceWindow;
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

        if ($validated['mode'] === 'recurring') {
            $timezone = $validated['recurring_timezone'];
            $startsAt = Date::parse($validated['recurring_starts_at'], $timezone)->setTimezone('UTC');

            MaintenanceWindow::query()->create([
                ...$this->recurringTarget($maintenanceRequest),
                'starts_at' => $startsAt,
                'duration_minutes' => (int) $validated['recurring_duration_minutes'],
                'recurrence' => $validated['recurrence'],
                'repeat_until' => isset($validated['recurring_repeat_until'])
                    ? Date::parse($validated['recurring_repeat_until'], $timezone)->endOfDay()->setTimezone('UTC')
                    : null,
                'timezone' => $timezone,
                'enabled' => true,
            ]);

            $message = __('maintenance.messages.recurring_scheduled');

            if ($maintenanceRequest->expectsJson()) {
                return response()->json(['message' => $message, 'updated_count' => 0]);
            }

            return to_route('maintenance.index')->with('success', $message);
        }

        $updatedCount = $this->targetMonitorings($maintenanceRequest)
            ->update([
                'maintenance_from' => Date::parse($validated['maintenance_from']),
                'maintenance_until' => isset($validated['maintenance_until'])
                    ? Date::parse($validated['maintenance_until'])
                    : null,
            ]);

        $message = trans_choice('maintenance.messages.scheduled', $updatedCount, ['count' => $updatedCount]);

        if ($maintenanceRequest->expectsJson()) {
            return response()->json(['message' => $message, 'updated_count' => $updatedCount]);
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
                'nullable',
                'required_without:maintenance_window_id',
                'string',
                function ($attribute, $value, $fail) use ($user): void {
                    if (! Monitoring::query()->manageableBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => __('maintenance.form.monitoring')]));
                    }
                },
            ],
            'maintenance_window_id' => [
                'nullable',
                'required_without:monitoring_id',
                'string',
                function ($attribute, $value, $fail) use ($user): void {
                    $window = MaintenanceWindow::query()->find((string) $value);

                    if (! $window || ! $window->isManageableBy($user)) {
                        $fail(__('validation.exists', ['attribute' => __('maintenance.form.recurring')]));
                    }
                },
            ],
        ]);

        if (isset($validated['maintenance_window_id'])) {
            MaintenanceWindow::query()->whereKey($validated['maintenance_window_id'])->update(['enabled' => false]);

            $message = __('maintenance.messages.recurring_cleared');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message]);
            }

            return to_route('maintenance.index')->with('success', $message);
        }

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

    /**
     * @return array{monitoring_id?: string, monitoring_group_id?: string}
     */
    private function recurringTarget(MaintenanceRequest $maintenanceRequest): array
    {
        if ($maintenanceRequest->string('scope')->toString() === 'group') {
            return ['monitoring_group_id' => $maintenanceRequest->string('monitoring_group_id')->toString()];
        }

        return ['monitoring_id' => $maintenanceRequest->string('monitoring_id')->toString()];
    }

    private function applyMaintenanceStatusFilter(Builder $builder, string $maintenanceStatus): void
    {
        if ($maintenanceStatus === '') {
            return;
        }

        $now = Date::now();

        match ($maintenanceStatus) {
            'active' => $builder
                ->whereNotNull('maintenance_from')
                ->where('maintenance_from', '<=', $now)
                ->where(function (Builder $builder) use ($now): void {
                    $builder->whereNull('maintenance_until')
                        ->orWhere('maintenance_until', '>=', $now);
                }),
            'upcoming' => $builder
                ->whereNotNull('maintenance_from')
                ->where('maintenance_from', '>', $now),
            'expired' => $builder
                ->whereNotNull('maintenance_from')
                ->whereNotNull('maintenance_until')
                ->where('maintenance_until', '<', $now),
            'none' => $builder->whereNull('maintenance_from'),
            default => null,
        };
    }

    private function applyMaintenanceSort(Builder $builder, string $sort, string $direction): void
    {
        if ($sort === 'maintenance_status') {
            $now = Date::now();
            $builder
                ->orderByRaw(
                    "case
                        when maintenance_from is not null and maintenance_from <= ? and (maintenance_until is null or maintenance_until >= ?) then 0
                        when maintenance_from is not null and maintenance_from > ? then 1
                        when maintenance_from is not null then 2
                        else 3
                    end {$direction}",
                    [$now, $now, $now]
                )
                ->orderBy('name');

            return;
        }

        $builder
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->orderBy('id');
    }
}
