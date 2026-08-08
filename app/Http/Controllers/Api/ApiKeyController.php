<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeyService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $state = $request->string('state')->toString();
        abort_unless(in_array($state, ['', 'active', 'revoked'], true), 422);

        $lengthAwarePaginator = $this->apiKeyService->paginate($user, min(max($request->integer('per_page', 25), 1), 100), $state ?: null);

        return response()->json([
            'data' => ApiKeyResource::collection($lengthAwarePaginator->items())->resolve($request),
            'meta' => [
                'current_page' => $lengthAwarePaginator->currentPage(),
                'last_page' => $lengthAwarePaginator->lastPage(),
                'per_page' => $lengthAwarePaginator->perPage(),
                'total' => $lengthAwarePaginator->total(),
            ],
        ]);
    }

    public function store(StoreApiKeyRequest $storeApiKeyRequest): JsonResponse
    {
        /** @var User $user */
        $user = $storeApiKeyRequest->user();
        $validated = $storeApiKeyRequest->validated();
        $newAccessToken = $this->apiKeyService->create($user, $validated['name'], $validated['abilities']);

        return response()->json([
            'data' => [
                'token' => $newAccessToken->plainTextToken,
                'key' => (new ApiKeyResource($newAccessToken->accessToken))->resolve($storeApiKeyRequest),
            ],
        ], 201);
    }

    public function show(Request $request, int $apiKey): JsonResponse
    {
        return response()->json([
            'data' => new ApiKeyResource($this->tokenForRequest($request, $apiKey)),
        ]);
    }

    public function destroy(Request $request, int $apiKey): JsonResponse
    {
        $personalAccessToken = $this->tokenForRequest($request, $apiKey);
        $this->apiKeyService->revoke($personalAccessToken);

        return response()->json([
            'data' => new ApiKeyResource($personalAccessToken->fresh()),
        ]);
    }

    private function tokenForRequest(Request $request, int $tokenId): PersonalAccessToken
    {
        /** @var User $user */
        $user = $request->user();
        $token = $this->apiKeyService->findForUser($user, $tokenId);

        abort_unless($token instanceof PersonalAccessToken, 404);

        return $token;
    }
}
