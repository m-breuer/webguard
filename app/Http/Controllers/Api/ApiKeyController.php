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

        $keys = $this->apiKeyService->paginate($user, min(max($request->integer('per_page', 25), 1), 100), $state ?: null);

        return response()->json([
            'data' => ApiKeyResource::collection($keys->items())->resolve($request),
            'meta' => [
                'current_page' => $keys->currentPage(),
                'last_page' => $keys->lastPage(),
                'per_page' => $keys->perPage(),
                'total' => $keys->total(),
            ],
        ]);
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $newToken = $this->apiKeyService->create($user, $validated['name'], $validated['abilities']);

        return response()->json([
            'data' => [
                'token' => $newToken->plainTextToken,
                'key' => (new ApiKeyResource($newToken->accessToken))->resolve($request),
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
        $token = $this->tokenForRequest($request, $apiKey);
        $this->apiKeyService->revoke($token);

        return response()->json([
            'data' => new ApiKeyResource($token->fresh()),
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
