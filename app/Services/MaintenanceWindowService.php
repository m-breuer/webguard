<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MaintenanceWindowRecurrence;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class MaintenanceWindowService
{
    /**
     * @return array{starts_at: CarbonInterface, ends_at: CarbonInterface, active: bool, recurring: bool}|null
     */
    public function currentOrUpcoming(Monitoring $monitoring, ?CarbonInterface $at = null): ?array
    {
        $at ??= Date::now();
        $windows = $this->recurringWindows($monitoring)
            ->map(fn (MaintenanceWindow $window): ?array => $this->resolveOccurrence($window, $at))
            ->filter()
            ->sortBy(fn (array $window): int => $window['active'] ? 0 : $window['starts_at']->getTimestamp());

        return $windows->first();
    }

    public function isUnderMaintenance(Monitoring $monitoring, ?CarbonInterface $at = null): bool
    {
        $window = $this->currentOrUpcoming($monitoring, $at);

        return $window !== null && $window['active'];
    }

    /**
     * @return Collection<int, MaintenanceWindow>
     */
    private function recurringWindows(Monitoring $monitoring): Collection
    {
        if (array_key_exists('has_enabled_maintenance_windows', $monitoring->getAttributes())
            && ! $monitoring->has_enabled_maintenance_windows) {
            return collect();
        }

        return MaintenanceWindow::query()
            ->enabled()
            ->where(function ($query) use ($monitoring): void {
                $query
                    ->where('monitoring_id', $monitoring->getKey())
                    ->orWhereHas('monitoringGroup.monitorings', fn ($query) => $query->whereKey($monitoring->getKey()));
            })
            ->get();
    }

    /**
     * @return array{starts_at: CarbonInterface, ends_at: CarbonInterface, active: bool, recurring: bool}|null
     */
    private function resolveOccurrence(MaintenanceWindow $window, CarbonInterface $at): ?array
    {
        $timezone = $window->timezone;
        $anchor = $window->starts_at->copy()->setTimezone($timezone);
        $reference = $at->copy()->setTimezone($timezone);

        if ($reference->lt($anchor)) {
            $startsAt = $anchor;
        } else {
            $startsAt = match ($window->recurrence) {
                MaintenanceWindowRecurrence::WEEKLY => $anchor->copy()->addWeeks(
                    intdiv((int) $anchor->diffInDays($reference), 7)
                ),
                MaintenanceWindowRecurrence::MONTHLY => $anchor->copy()->addMonthsNoOverflow(
                    (($reference->year - $anchor->year) * 12) + ($reference->month - $anchor->month)
                ),
            };

            $endsAt = $startsAt->copy()->addMinutes($window->duration_minutes);

            if ($endsAt->lt($reference)) {
                $startsAt = match ($window->recurrence) {
                    MaintenanceWindowRecurrence::WEEKLY => $startsAt->copy()->addWeek(),
                    MaintenanceWindowRecurrence::MONTHLY => $startsAt->copy()->addMonthNoOverflow(),
                };
            }
        }

        if ($window->repeat_until && $startsAt->gt($window->repeat_until->copy()->setTimezone($timezone))) {
            return null;
        }

        $endsAt = $startsAt->copy()->addMinutes($window->duration_minutes);

        return [
            'starts_at' => $startsAt->copy()->setTimezone(Date::now()->getTimezone()),
            'ends_at' => $endsAt->copy()->setTimezone(Date::now()->getTimezone()),
            'active' => $startsAt->lte($reference) && $endsAt->gte($reference),
            'recurring' => true,
        ];
    }
}
