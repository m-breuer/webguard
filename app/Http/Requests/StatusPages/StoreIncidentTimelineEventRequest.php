<?php

declare(strict_types=1);

namespace App\Http\Requests\StatusPages;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentTimelineEventRequest extends FormRequest
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
            'occurred_at' => ['required', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => mb_trim((string) $this->input('title')),
            'description' => $this->nullableTrimmedInput('description'),
        ]);
    }

    private function nullableTrimmedInput(string $key): ?string
    {
        $value = mb_trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
