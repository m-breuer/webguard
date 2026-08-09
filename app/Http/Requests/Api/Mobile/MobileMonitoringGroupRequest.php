<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Mobile;

use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MobileMonitoringGroupRequest extends FormRequest
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
        /** @var MonitoringGroup|string|null $monitoringGroup */
        $monitoringGroup = $this->route('monitoringGroup');
        $monitoringGroupId = $monitoringGroup instanceof MonitoringGroup
            ? $monitoringGroup->id
            : $monitoringGroup;

        return [
            'name' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:100',
                Rule::unique('monitoring_groups', 'name')
                    ->where('user_id', $this->user()?->id)
                    ->ignore($monitoringGroupId),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'monitoring_ids' => ['sometimes', 'array', 'max:100'],
            'monitoring_ids.*' => [
                'string',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $user = $this->user();

                    if ($user === null || ! Monitoring::query()->privateOwnedBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('monitoring_group.validation.monitoring_not_manageable'));
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $prepared = [];

        if ($this->has('name')) {
            $prepared['name'] = mb_trim((string) $this->input('name'));
        }

        if ($this->has('description')) {
            $description = mb_trim((string) $this->input('description'));
            $prepared['description'] = $description !== '' ? $description : null;
        }

        if ($this->has('monitoring_ids')) {
            $monitoringIds = $this->input('monitoring_ids', []);
            if (! is_array($monitoringIds)) {
                $monitoringIds = [$monitoringIds];
            }

            $prepared['monitoring_ids'] = array_values(array_filter(
                array_map(static fn (mixed $monitoringId): string => (string) $monitoringId, $monitoringIds),
                static fn (string $monitoringId): bool => $monitoringId !== ''
            ));
        }

        $this->merge($prepared);
    }
}
