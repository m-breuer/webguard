<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $windows = Monitoring::query()
            ->manageableBy($user)
            ->select(['id', 'name', 'target', 'maintenance_from', 'maintenance_until'])
            ->with('groups:id,name')
            ->orderBy('name')
            ->paginate(min(max($request->integer('per_page', 50), 1), 100));

        $windowData = $windows->through(static fn (Monitoring $monitoring): array => [
            'id' => (string) $monitoring->id,
            'name' => $monitoring->name,
            'target' => $monitoring->target,
            'groups' => $monitoring->groups->map(static fn ($group): array => [
                'id' => (string) $group->id,
                'name' => $group->name,
            ])->values()->all(),
            'status' => match (true) {
                $monitoring->isUnderMaintenance() => 'active',
                $monitoring->maintenance_from?->isFuture() === true => 'upcoming',
                $monitoring->maintenance_from !== null => 'expired',
                default => 'none',
            },
            'maintenance_from' => $monitoring->maintenance_from?->toDayDateTimeString(),
            'maintenance_until' => $monitoring->maintenance_until?->toDayDateTimeString(),
        ]);

        return response()->json([
            'data' => [
                'windows' => $windowData,
                'monitoring_options' => Monitoring::query()
                    ->manageableBy($user)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(static fn (Monitoring $monitoring): array => [
                        'id' => (string) $monitoring->id,
                        'name' => $monitoring->name,
                    ])
                    ->values()
                    ->all(),
                'monitoring_groups' => $user->monitoringGroups()
                    ->withCount('monitorings')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(static fn ($group): array => [
                        'id' => (string) $group->id,
                        'name' => $group->name,
                        'monitorings_count' => (int) $group->monitorings_count,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
