<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonitoringRequest;
use App\Http\Resources\External\MonitoringResource;
use App\Models\Monitoring;
use App\Models\Team;
use App\Models\User;
use App\Services\Monitorings\MonitoringOwnershipService;
use App\Support\MonitoringPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(min(max((int) $request->integer('per_page', 25), 1), 100));

        $monitorings->setCollection($monitorings->getCollection()->map(
            fn (Monitoring $monitoring): array => MonitoringResource::make($monitoring)->resolve($request)
        ));

        return response()->json($monitorings);
    }

    public function store(MonitoringRequest $monitoringRequest): JsonResponse
    {
        /** @var User $user */
        $user = $monitoringRequest->user();
        abort_if($user->isDemo(), 403);

        if ($user->monitorings()->whereNull('team_id')->count() >= $user->package->monitoring_limit
            && blank($monitoringRequest->input('team_id'))) {
            return response()->json(['message' => __('monitoring.messages.limit_reached')], 422);
        }

        $validated = $monitoringRequest->validated();
        $teamId = $validated['team_id'] ?? null;
        unset($validated['group_ids'], $validated['team_id']);

        $payload = MonitoringPayload::prepareStore($validated);

        if ($teamId) {
            $payload['user_id'] = null;
            $payload['team_id'] = $teamId;
            $payload['created_by_user_id'] = $user->id;

            $monitoring = Monitoring::query()->create($payload);
        } else {
            $monitoring = $user->monitorings()->create($payload);
        }

        return response()->json(['data' => MonitoringResource::make($monitoring)->resolve($monitoringRequest)], 201);
    }

    public function update(MonitoringRequest $monitoringRequest, Monitoring $monitoring): JsonResponse
    {
        /** @var User $user */
        $user = $monitoringRequest->user();
        abort_if($user->isDemo(), 403);
        abort_unless($monitoring->isManageableBy($user), 403);

        $validated = $monitoringRequest->validated();
        unset($validated['group_ids'], $validated['team_id']);

        $payload = MonitoringPayload::prepareUpdate($validated, $monitoring);
        if (! isset($payload['public_label_enabled']) || ! $payload['public_label_enabled']) {
            $payload['public_label_enabled'] = false;
        }

        $monitoring->update($payload);

        return response()->json(['data' => MonitoringResource::make($monitoring->refresh())->resolve($monitoringRequest)]);
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
            'data' => MonitoringResource::make($monitoringOwnershipService->moveToTeam($monitoring, $team, $user))->resolve($request),
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
            'data' => MonitoringResource::make($monitoringOwnershipService->moveToPrivate($monitoring, $user))->resolve($request),
        ]);
    }
}
