<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(['monitoring', 'group'])],
            'monitoring_id' => [
                'nullable',
                'required_if:scope,monitoring',
                'string',
                Rule::exists('monitorings', 'id')->where('user_id', $this->user()?->id),
            ],
            'monitoring_group_id' => [
                'nullable',
                'required_if:scope,group',
                'string',
                Rule::exists('monitoring_groups', 'id')->where('user_id', $this->user()?->id),
            ],
            'maintenance_from' => ['required', 'date'],
            'maintenance_until' => ['nullable', 'date', 'after:maintenance_from'],
        ];
    }
}
