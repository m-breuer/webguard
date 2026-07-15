<?php

declare(strict_types=1);

namespace App\Http\Requests\StatusPages;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_user_id' => ['nullable', 'ulid', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => mb_trim((string) $this->input('title')),
            'description' => $this->nullableTrimmedInput('description'),
            'assigned_user_id' => $this->nullableTrimmedInput('assigned_user_id'),
            'external_url' => $this->nullableTrimmedInput('external_url'),
        ]);
    }

    private function nullableTrimmedInput(string $key): ?string
    {
        $value = mb_trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
