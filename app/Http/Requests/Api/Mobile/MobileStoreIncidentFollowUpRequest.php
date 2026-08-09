<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Mobile;

use App\Http\Requests\Api\Mobile\Concerns\RequiresMobileIdempotencyKey;
use App\Http\Requests\StatusPages\StoreIncidentFollowUpRequest;

class MobileStoreIncidentFollowUpRequest extends StoreIncidentFollowUpRequest
{
    use RequiresMobileIdempotencyKey;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'idempotency_key' => $this->mobileIdempotencyKeyRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->prepareMobileIdempotencyKey();
    }
}
