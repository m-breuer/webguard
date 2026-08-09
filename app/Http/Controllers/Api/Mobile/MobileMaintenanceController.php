<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\MobileStoreMaintenanceRequest;
use App\Http\Requests\Api\Mobile\UpdateMobileMaintenanceWindowRequest;
use App\Http\Resources\External\MobileMaintenanceWindowResource;
use App\Http\Resources\External\MobileOneOffMaintenanceResource;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\User;
use App\Services\MobileMaintenanceWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MobileMaintenanceController extends Controller
{
    public function capabilities(Request $request, MobileMaintenanceWorkspaceService $mobileMaintenanceWorkspaceService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $monitorings = Monitoring::query()
            ->manageableBy($user)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'team_id']);

        return response()->json([
            'data' => [
                'can_schedule' => ! $user->isDemo() && $monitorings->isNotEmpty(),
                'manageable_monitoring_ids' => $monitorings->pluck('id')->values()->all(),
                'manageable_monitorings' => $monitorings->map(fn (Monitoring $monitoring): array => [
                    'id' => $monitoring->id,
                    'name' => $monitoring->name,
                    'ownership' => $monitoring->team_id === null ? 'private' : 'team_admin',
                ])->values()->all(),
                'monitoring_groups' => $user->monitoringGroups()
                    ->withCount('monitorings')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($group): array => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'monitorings_count' => (int) $group->monitorings_count,
                    ])->values()->all(),
                'idempotency' => [
                    'create_header' => 'Idempotency-Key',
                    'required_for_create' => true,
                ],
            ],
        ]);
    }

    public function oneOffIndex(Request $request, MobileMaintenanceWorkspaceService $mobileMaintenanceWorkspaceService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $state = $this->state($request, ['active', 'upcoming', 'expired']);
        $lengthAwarePaginator = $mobileMaintenanceWorkspaceService->oneOffWindowsFor($user, $state)
            ->paginate($this->perPage($request));
        $lengthAwarePaginator->setCollection($lengthAwarePaginator->getCollection()->map(
            fn (Monitoring $monitoring): array => MobileOneOffMaintenanceResource::make(
                $mobileMaintenanceWorkspaceService->decorateOneOff($monitoring, $user)
            )->resolve($request)
        ));

        return response()->json($lengthAwarePaginator);
    }

    public function recurringIndex(Request $request, MobileMaintenanceWorkspaceService $mobileMaintenanceWorkspaceService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $state = $this->state($request, ['active', 'upcoming', 'expired', 'disabled']);
        $perPage = $this->perPage($request);
        $builder = $mobileMaintenanceWorkspaceService->recurringWindowsFor($user, $state);

        if (in_array($state, ['active', 'upcoming', 'expired'], true)) {
            $allWindows = $builder->get()
                ->map(fn (MaintenanceWindow $maintenanceWindow): MaintenanceWindow => $mobileMaintenanceWorkspaceService->decorateRecurring($maintenanceWindow, $user))
                ->filter(fn (MaintenanceWindow $maintenanceWindow): bool => $maintenanceWindow->getAttribute('mobile_state') === $state)
                ->values();
            $windows = new LengthAwarePaginator(
                $allWindows->forPage($request->integer('page', 1), $perPage)->values(),
                $allWindows->count(),
                $perPage,
                $request->integer('page', 1),
                ['path' => $request->url(), 'query' => $request->query()],
            );
        } else {
            $windows = $builder->paginate($perPage);
        }

        $windows->setCollection($windows->getCollection()->map(
            fn (MaintenanceWindow $maintenanceWindow): array => MobileMaintenanceWindowResource::make(
                $mobileMaintenanceWorkspaceService->decorateRecurring($maintenanceWindow, $user)
            )->resolve($request)
        ));

        return response()->json($windows);
    }

    public function store(MobileStoreMaintenanceRequest $mobileStoreMaintenanceRequest, MobileMaintenanceWorkspaceService $mobileMaintenanceWorkspaceService): JsonResponse
    {
        /** @var User $user */
        $user = $mobileStoreMaintenanceRequest->user();
        $result = $mobileMaintenanceWorkspaceService->schedule($user, $mobileStoreMaintenanceRequest->validated());

        return response()->json([
            'data' => $result['operation']->result,
            'idempotent' => ! $result['created'],
        ], $result['created'] ? 201 : 200);
    }

    public function updateRecurring(
        UpdateMobileMaintenanceWindowRequest $updateMobileMaintenanceWindowRequest,
        string $maintenanceWindow,
        MobileMaintenanceWorkspaceService $mobileMaintenanceWorkspaceService,
    ): JsonResponse {
        /** @var User $user */
        $user = $updateMobileMaintenanceWindowRequest->user();
        $window = $mobileMaintenanceWorkspaceService->updateRecurringEnabled(
            $mobileMaintenanceWorkspaceService->recurringWindowFor($user, $maintenanceWindow),
            $user,
            $updateMobileMaintenanceWindowRequest->boolean('enabled'),
        );

        return response()->json([
            'data' => MobileMaintenanceWindowResource::make(
                $mobileMaintenanceWorkspaceService->decorateRecurring($window, $user)
            )->resolve($updateMobileMaintenanceWindowRequest),
        ]);
    }

    public function cancelOneOff(Request $request, string $monitoring, MobileMaintenanceWorkspaceService $mobileMaintenanceWorkspaceService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $mobileMaintenanceWorkspaceService->cancelOneOff(
            Monitoring::query()->visibleTo($user)->whereKey($monitoring)->firstOrFail(),
            $user,
        );

        return response()->json(status: 204);
    }

    private function state(Request $request, array $allowed): ?string
    {
        $state = $request->string('state')->toString();
        abort_unless($state === '' || in_array($state, $allowed, true), 422);

        return $state === '' ? null : $state;
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 25), 1), 100);
    }
}
