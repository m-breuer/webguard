<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\User;
use App\Services\PlannedMaintenanceNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class MaintenanceController extends Controller
{
    public function store(
        MaintenanceRequest $maintenanceRequest,
        PlannedMaintenanceNotificationService $plannedMaintenanceNotificationService,
    ): JsonResponse {
        abort_if($maintenanceRequest->user()?->isDemo(), 403);

        $validated = $maintenanceRequest->validated();

        if ($validated['mode'] === 'recurring') {
            $timezone = $validated['recurring_timezone'];
            $startsAt = Date::parse($validated['recurring_starts_at'], $timezone)->setTimezone('UTC');

            $maintenanceWindow = MaintenanceWindow::query()->create([
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
            $plannedMaintenanceNotificationService->notifyForRecurring($maintenanceWindow);

            $message = __('maintenance.messages.recurring_scheduled');

            return response()->json(['message' => $message, 'updated_count' => 0]);
        }

        $monitorings = $this->targetMonitorings($maintenanceRequest)->get();
        $updatedCount = $this->targetMonitorings($maintenanceRequest)
            ->update([
                'maintenance_from' => Date::parse($validated['maintenance_from']),
                'maintenance_until' => isset($validated['maintenance_until'])
                    ? Date::parse($validated['maintenance_until'])
                    : null,
            ]);
        $monitorings = Monitoring::query()->whereKey($monitorings->modelKeys())->get();
        $plannedMaintenanceNotificationService->notifyForOneOff(
            $monitorings,
            Date::parse($validated['maintenance_from']),
            isset($validated['maintenance_until']) ? Date::parse($validated['maintenance_until']) : null,
        );

        $message = trans_choice('maintenance.messages.scheduled', $updatedCount, ['count' => $updatedCount]);

        return response()->json(['message' => $message, 'updated_count' => $updatedCount]);
    }

    public function update(
        UpdateMaintenanceRequest $updateMaintenanceRequest,
        PlannedMaintenanceNotificationService $plannedMaintenanceNotificationService,
    ): JsonResponse {
        abort_if($updateMaintenanceRequest->user()?->isDemo(), 403);

        $validated = $updateMaintenanceRequest->validated();

        if ($validated['mode'] === 'recurring') {
            $timezone = $validated['recurring_timezone'];
            $maintenanceWindow = MaintenanceWindow::query()->findOrFail($validated['maintenance_window_id']);
            $maintenanceWindow->update([
                'monitoring_id' => null,
                'monitoring_group_id' => null,
                ...$this->recurringTarget($updateMaintenanceRequest),
                'starts_at' => Date::parse($validated['recurring_starts_at'], $timezone)->setTimezone('UTC'),
                'duration_minutes' => (int) $validated['recurring_duration_minutes'],
                'recurrence' => $validated['recurrence'],
                'repeat_until' => isset($validated['recurring_repeat_until'])
                    ? Date::parse($validated['recurring_repeat_until'], $timezone)->endOfDay()->setTimezone('UTC')
                    : null,
                'timezone' => $timezone,
            ]);
            $plannedMaintenanceNotificationService->notifyForRecurring(
                MaintenanceWindow::query()->findOrFail($maintenanceWindow->id)
            );

            $message = __('maintenance.messages.recurring_updated');

            return response()->json(['message' => $message, 'updated_count' => 0]);
        }

        $monitorings = $this->targetMonitorings($updateMaintenanceRequest)->get();
        $updatedCount = $this->targetMonitorings($updateMaintenanceRequest)
            ->update([
                'maintenance_from' => Date::parse($validated['maintenance_from']),
                'maintenance_until' => isset($validated['maintenance_until'])
                    ? Date::parse($validated['maintenance_until'])
                    : null,
            ]);
        $monitorings = Monitoring::query()->whereKey($monitorings->modelKeys())->get();
        $plannedMaintenanceNotificationService->notifyForOneOff(
            $monitorings,
            Date::parse($validated['maintenance_from']),
            isset($validated['maintenance_until']) ? Date::parse($validated['maintenance_until']) : null,
        );

        $message = trans_choice('maintenance.messages.updated', $updatedCount, ['count' => $updatedCount]);

        return response()->json(['message' => $message, 'updated_count' => $updatedCount]);
    }

    public function destroy(Request $request): JsonResponse
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

            return response()->json(['message' => $message]);
        }

        Monitoring::query()
            ->manageableBy($user)
            ->whereKey($validated['monitoring_id'])
            ->update([
                'maintenance_from' => null,
                'maintenance_until' => null,
            ]);

        $message = __('maintenance.messages.cleared');

        return response()->json(['message' => $message]);
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
}
