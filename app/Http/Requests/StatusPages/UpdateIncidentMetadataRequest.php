<?php

declare(strict_types=1);

namespace App\Http\Requests\StatusPages;

use App\Enums\IncidentContributingCategory;
use App\Enums\IncidentCustomerImpact;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentMetadataRequest extends FormRequest
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
            'incident_type' => ['nullable', Rule::enum(IncidentType::class)],
            'severity' => ['nullable', Rule::enum(IncidentSeverity::class)],
            'affected_service' => ['nullable', 'string', 'max:255'],
            'customer_impact' => ['nullable', Rule::enum(IncidentCustomerImpact::class)],
            'contributing_category' => ['nullable', Rule::enum(IncidentContributingCategory::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'incident_type' => $this->nullableTrimmedInput('incident_type'),
            'severity' => $this->nullableTrimmedInput('severity'),
            'affected_service' => $this->nullableTrimmedInput('affected_service'),
            'customer_impact' => $this->nullableTrimmedInput('customer_impact'),
            'contributing_category' => $this->nullableTrimmedInput('contributing_category'),
        ]);
    }

    private function nullableTrimmedInput(string $key): ?string
    {
        $value = mb_trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
