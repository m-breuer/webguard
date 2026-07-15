<?php

declare(strict_types=1);

namespace App\Http\Requests\StatusPages;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentReviewRequest extends FormRequest
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
            'problem_description' => ['nullable', 'string', 'max:5000'],
            'resolution_description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'problem_description' => $this->nullableTrimmedInput('problem_description'),
            'resolution_description' => $this->nullableTrimmedInput('resolution_description'),
        ]);
    }

    private function nullableTrimmedInput(string $key): ?string
    {
        $value = mb_trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
