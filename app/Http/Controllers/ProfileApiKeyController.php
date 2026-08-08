<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreApiKeyRequest;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeyService) {}

    public function store(StoreApiKeyRequest $storeApiKeyRequest): RedirectResponse
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

        return to_route('profile.edit', ['#api-keys'])
            ->with('api_key_plaintext', $newAccessToken->plainTextToken)
            ->with('success', __('api.configuration.messages.created'));
    }

    public function destroy(Request $request, int $apiKey): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $this->apiKeyService->findForUser($user, $apiKey);
        abort_unless($token instanceof PersonalAccessToken, 404);

        $revoked = $this->apiKeyService->revoke($token);

        if ($revoked) {
            activity('user')
                ->performedOn($user)
                ->event('api_key_revoked')
                ->withProperties(['action' => 'api_key_revoked', 'key_id' => $token->getKey()])
                ->log('user_api_key_revoked');
        }

        return to_route('profile.edit', ['#api-keys'])
            ->with('success', __('api.configuration.messages.revoked'));
    }
}
