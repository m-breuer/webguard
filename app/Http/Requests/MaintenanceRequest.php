<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MaintenanceWindowRecurrence;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isDemo();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', Rule::in(['one_off', 'recurring'])],
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
            'maintenance_from' => ['nullable', 'required_if:mode,one_off', 'date'],
            'maintenance_until' => ['nullable', 'date', 'after:maintenance_from'],
            'recurring_starts_at' => ['nullable', 'required_if:mode,recurring', 'date'],
            'recurring_duration_minutes' => ['nullable', 'required_if:mode,recurring', 'integer', 'min:1', 'max:1440'],
            'recurrence' => ['nullable', 'required_if:mode,recurring', Rule::enum(MaintenanceWindowRecurrence::class)],
            'recurring_repeat_until' => ['nullable', 'date', 'after:recurring_starts_at'],
            'recurring_timezone' => ['nullable', 'required_if:mode,recurring', 'timezone'],
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', 'one_off'),
        ]);
    }
}
