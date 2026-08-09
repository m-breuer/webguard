<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

final class MobileMonitoringDetailRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'incident_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'incident_offset' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function days(): int
    {
        return (int) ($this->validated('days') ?? 30);
    }

    public function incidentLimit(): int
    {
        return (int) ($this->validated('incident_limit') ?? 20);
    }

    public function incidentOffset(): int
    {
        return (int) ($this->validated('incident_offset') ?? 0);
    }
}
