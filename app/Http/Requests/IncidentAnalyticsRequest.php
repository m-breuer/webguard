<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IncidentCustomerImpact;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentAnalyticsRequest extends FormRequest
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
            'days' => ['nullable', 'integer', Rule::in([30, 90, 365])],
            'incident_type' => ['nullable', Rule::enum(IncidentType::class)],
            'severity' => ['nullable', Rule::enum(IncidentSeverity::class)],
            'customer_impact' => ['nullable', Rule::enum(IncidentCustomerImpact::class)],
            'affected_service' => ['nullable', 'string', 'max:255'],
        ];
    }
}
