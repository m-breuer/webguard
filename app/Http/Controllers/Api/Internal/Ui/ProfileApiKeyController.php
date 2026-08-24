<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApiKeyRequest;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeyService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->apiKeyService->paginate($user, 100, null)
                ->getCollection()
                ->map(fn (PersonalAccessToken $personalAccessToken): array => $this->payload($personalAccessToken))
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreApiKeyRequest $storeApiKeyRequest): JsonResponse
    {
        /** @var User $user */
        $user = $storeApiKeyRequest->user();
        $validated = $storeApiKeyRequest->validated();
        $newAccessToken = $this->apiKeyService->create($user, $validated['name'], $validated['abilities']);

        activity('user')
            ->performedOn($user)
            ->event('api_key_created')
            ->withProperties(['action' => 'api_key_created', 'key_id' => $newAccessToken->accessToken->getKey()])
            ->log('user_api_key_created');

        return response()->json([
            'data' => [
                'api_key' => $this->payload($newAccessToken->accessToken),
                'token' => $newAccessToken->plainTextToken,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $apiKey): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $this->apiKeyService->findForUser($user, $apiKey);
        abort_unless($token instanceof PersonalAccessToken, 404);

        if ($this->apiKeyService->revoke($token)) {
            activity('user')
                ->performedOn($user)
                ->event('api_key_revoked')
                ->withProperties(['action' => 'api_key_revoked', 'key_id' => $token->getKey()])
                ->log('user_api_key_revoked');
        }

        return response()->json([
            'data' => $this->payload($token->refresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PersonalAccessToken $personalAccessToken): array
    {
        return [
            'id' => $personalAccessToken->getKey(),
            'name' => ApiKeyService::displayName($personalAccessToken),
            'abilities' => $personalAccessToken->abilities ?? [],
            'last_used_at' => $personalAccessToken->last_used_at?->toIso8601String(),
            'revoked_at' => $personalAccessToken->revoked_at?->toIso8601String(),
        ];
    }
}
