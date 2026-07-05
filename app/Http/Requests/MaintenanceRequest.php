<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->string('scope')->toString() !== 'monitoring' || $validator->errors()->has('monitoring_id')) {
                    return;
                }

                $user = $this->user();
                $monitoringId = $this->string('monitoring_id')->toString();

                if (! $user instanceof User || $monitoringId === '') {
                    return;
                }

                $isManageable = Monitoring::query()
                    ->manageableBy($user)
                    ->whereKey($monitoringId)
                    ->exists();

                if (! $isManageable) {
                    $validator->errors()->add('monitoring_id', __('validation.exists', [
                        'attribute' => __('maintenance.form.monitoring'),
                    ]));
                }
            },
        ];
    }
}
