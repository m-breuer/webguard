<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusPages\StatusPageRequest;
use App\Http\Resources\External\MobileStatusPageResource;
use App\Models\Monitoring;
use App\Models\StatusPage;
use App\Models\User;
use App\Services\MobileStatusPageWorkspaceService;
use App\Services\StatusPageManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusPageManagementController extends Controller
{
    public function __construct(
        private readonly MobileStatusPageWorkspaceService $mobileStatusPageWorkspaceService,
        private readonly StatusPageManagementService $statusPageManagementService,
    ) {}

    public function options(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'monitorings' => Monitoring::query()
                    ->visibleTo($user)
                    ->orderBy('name')
                    ->get(['id', 'name', 'target'])
                    ->map(fn (Monitoring $monitoring): array => [
                        'id' => $monitoring->id,
                        'name' => $monitoring->name,
                        'target' => $monitoring->target,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function store(StatusPageRequest $statusPageRequest): JsonResponse
    {
        abort_if($statusPageRequest->user()->isDemo(), 403);

        /** @var User $user */
        $user = $statusPageRequest->user();
        $statusPage = $this->statusPageManagementService->create($user, $statusPageRequest->validated());

        return response()->json(['data' => $this->payload($user, $statusPage)], 201);
    }

    public function update(StatusPageRequest $statusPageRequest, string $statusPage): JsonResponse
    {
        abort_if($statusPageRequest->user()->isDemo(), 403);

        /** @var User $user */
        $user = $statusPageRequest->user();
        $statusPageModel = $this->statusPageFor($user, $statusPage);
        $this->statusPageManagementService->update($statusPageModel, $statusPageRequest->validated());

        return response()->json(['data' => $this->payload($user, $statusPageModel)]);
    }

    public function destroy(Request $request, string $statusPage): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isDemo(), 403);

        $this->statusPageFor($user, $statusPage)->delete();

        return response()->json(status: 204);
    }

    private function statusPageFor(User $user, string $statusPage): StatusPage
    {
        return $this->mobileStatusPageWorkspaceService->statusPageFor($user, $statusPage);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user, StatusPage $statusPage): array
    {
        $workspace = $this->mobileStatusPageWorkspaceService->loadWorkspace($statusPage, $user);
        $workspace->setAttribute('open_incident_count', $this->mobileStatusPageWorkspaceService->openIncidentCount($workspace, $user));

        return MobileStatusPageResource::make($workspace)->resolve();
    }
}
