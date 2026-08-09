<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonitoringRequest;
use App\Http\Resources\External\MonitoringResource;
use App\Models\Monitoring;
use App\Models\Team;
use App\Models\User;
use App\Services\Monitorings\MonitoringGroupAssignmentService;
use App\Services\Monitorings\MonitoringOwnershipService;
use App\Support\MonitoringPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @group Monitoring Management
 *
 * Create, update, delete, and move private or team-owned monitorings.
 */
class MonitoringManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $monitorings = Monitoring::query()
            ->visibleTo($user)
            ->with([
                'groups' => fn ($query) => $query->where('user_id', $user->id)->select(['id', 'name', 'description']),
                'team.memberships' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(min(max((int) $request->integer('per_page', 25), 1), 100));

        $monitorings->setCollection($monitorings->getCollection()->map(
            fn (Monitoring $monitoring): array => MonitoringResource::make($monitoring)->resolve($request)
        ));

        return response()->json($monitorings);
    }

    public function store(
        MonitoringRequest $monitoringRequest,
        MonitoringGroupAssignmentService $monitoringGroupAssignmentService
    ): JsonResponse {
        /** @var User $user */
        $user = $monitoringRequest->user();
        abort_if($user->isDemo(), 403);

        if ($user->monitorings()->whereNull('team_id')->count() >= $user->package->monitoring_limit
            && blank($monitoringRequest->input('team_id'))) {
            return response()->json(['message' => __('monitoring.messages.limit_reached')], 422);
        }

        $validated = $monitoringRequest->validated();
        $teamId = $validated['team_id'] ?? null;
        $groupIds = $validated['group_ids'] ?? [];
        unset($validated['group_ids'], $validated['team_id']);

        if ($teamId !== null && $groupIds !== []) {
            throw ValidationException::withMessages([
                'group_ids' => [__('monitoring_group.validation.monitoring_not_manageable')],
            ]);
        }

        $payload = MonitoringPayload::prepareStore($validated);

        $monitoring = DB::transaction(function () use ($teamId, $payload, $user, $groupIds, $monitoringGroupAssignmentService): Monitoring {
            if ($teamId) {
                $payload['user_id'] = null;
                $payload['team_id'] = $teamId;
                $payload['created_by_user_id'] = $user->id;

                return Monitoring::query()->create($payload);
            }

            $monitoring = $user->monitorings()->create($payload);
            $monitoringGroupAssignmentService->syncGroupsForPrivateMonitoring($monitoring, $user, $groupIds);

            return $monitoring;
        });

        return response()->json(['data' => MonitoringResource::make($monitoring->load([
            'groups' => fn ($query) => $query->where('user_id', $user->id)->select(['id', 'name', 'description']),
        ]))->resolve($monitoringRequest)], 201);
    }

    public function update(
        MonitoringRequest $monitoringRequest,
        Monitoring $monitoring,
        MonitoringGroupAssignmentService $monitoringGroupAssignmentService
    ): JsonResponse {
        /** @var User $user */
        $user = $monitoringRequest->user();
        abort_if($user->isDemo(), 403);
        abort_unless($monitoring->isManageableBy($user), 403);

        $validated = $monitoringRequest->validated();
        $hasGroupIds = array_key_exists('group_ids', $validated);
        $groupIds = $validated['group_ids'] ?? [];
        unset($validated['group_ids'], $validated['team_id']);

        if ($hasGroupIds && ! $monitoring->isPrivateOwned()) {
            throw ValidationException::withMessages([
                'group_ids' => [__('monitoring_group.validation.monitoring_not_manageable')],
            ]);
        }

        $payload = MonitoringPayload::prepareUpdate($validated, $monitoring);
        if (! isset($payload['public_label_enabled']) || ! $payload['public_label_enabled']) {
            $payload['public_label_enabled'] = false;
        }

        DB::transaction(function () use ($monitoring, $payload, $hasGroupIds, $groupIds, $user, $monitoringGroupAssignmentService): void {
            $monitoring->update($payload);

            if ($hasGroupIds) {
                $monitoringGroupAssignmentService->syncGroupsForPrivateMonitoring($monitoring, $user, $groupIds);
            }
        });

        return response()->json(['data' => MonitoringResource::make($monitoring->refresh()->load([
            'groups' => fn ($query) => $query->where('user_id', $user->id)->select(['id', 'name', 'description']),
        ]))->resolve($monitoringRequest)]);
    }

    public function destroy(Request $request, Monitoring $monitoring): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);
        abort_unless($monitoring->isManageableBy($user), 403);

        $monitoring->delete();

        return response()->json(status: 204);
    }

    public function moveToTeam(
        Request $request,
        Monitoring $monitoring,
        MonitoringOwnershipService $monitoringOwnershipService
    ): JsonResponse {
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

        return response()->json([
            'data' => MonitoringResource::make($monitoringOwnershipService->moveToTeam($monitoring, $team, $user)->load([
                'groups' => fn ($query) => $query->where('user_id', $user->id)->select(['id', 'name', 'description']),
            ]))->resolve($request),
        ]);
    }

    public function moveToPrivate(
        Request $request,
        Monitoring $monitoring,
        MonitoringOwnershipService $monitoringOwnershipService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);

        return response()->json([
            'data' => MonitoringResource::make($monitoringOwnershipService->moveToPrivate($monitoring, $user)->load([
                'groups' => fn ($query) => $query->where('user_id', $user->id)->select(['id', 'name', 'description']),
            ]))->resolve($request),
        ]);
    }
}
