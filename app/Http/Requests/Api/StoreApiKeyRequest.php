<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\ApiKeyAbility;
use App\Models\User;
use App\Services\ApiKeyService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    $exists = $user->tokens()
                        ->where(function ($query) use ($value): void {
                            $query->where('name', ApiKeyService::storedName((string) $value));

                            if ($value === ApiKeyService::LEGACY_TOKEN_NAME) {
                                $query->orWhere('name', ApiKeyService::LEGACY_TOKEN_NAME);
                            }
                        })
                        ->exists();

                    if ($exists) {
                        $fail(__('validation.unique', ['attribute' => $attribute]));
                    }
                },
            ],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', Rule::in(ApiKeyAbility::values()), 'distinct:strict'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => mb_trim((string) $this->input('name')),
        ]);
    }
}
