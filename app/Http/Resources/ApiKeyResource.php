<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PersonalAccessToken;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonalAccessToken */
class ApiKeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => ApiKeyService::displayName($this->resource),
            'abilities' => $this->abilities,
            'token_prefix' => $this->getKey() . '|',
            'created_at' => $this->created_at?->toIso8601String(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revoked' => $this->revoked_at !== null,
            'legacy' => ! ApiKeyService::isManagedKey($this->resource),
        ];
    }
}
