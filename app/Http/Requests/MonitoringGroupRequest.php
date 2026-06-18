<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MonitoringGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoringGroupRequest extends FormRequest
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
        /** @var MonitoringGroup|null $monitoringGroup */
        $monitoringGroup = $this->route('monitoringGroup');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('monitoring_groups', 'name')
                    ->where('user_id', $this->user()?->id)
                    ->ignore($monitoringGroup?->id),
            ],
            'description' => ['nullable', 'string'],
            'public_label_enabled' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = mb_trim((string) $this->input('description', ''));

        $this->merge([
            'name' => mb_trim((string) $this->input('name', '')),
            'description' => $description !== '' ? $description : null,
            'public_label_enabled' => $this->boolean('public_label_enabled'),
        ]);
    }
}
