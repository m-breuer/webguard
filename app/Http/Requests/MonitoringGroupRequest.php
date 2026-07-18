<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Monitoring;
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
            'monitoring_ids' => ['nullable', 'array', 'max:100'],
            'monitoring_ids.*' => [
                'string',
                'distinct',
                function ($attribute, $value, $fail): void {
                    $user = $this->user();

                    if ($user === null || ! Monitoring::query()->privateOwnedBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('monitoring_group.validation.monitoring_not_manageable'));
                    }
                },
            ],
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->input('modal_form') === 'monitoring-group-create') {
            return route('monitoring-groups.index', ['modal' => 'monitoring-group-create']);
        }

        if ($this->input('modal_form') === 'monitoring-group-edit') {
            $monitoringGroup = $this->route('monitoringGroup');

            return route('monitoring-groups.index', [
                'modal' => 'monitoring-group-edit',
                'monitoring_group' => is_object($monitoringGroup) ? $monitoringGroup->getRouteKey() : $monitoringGroup,
            ]);
        }

        return parent::getRedirectUrl();
    }

    protected function prepareForValidation(): void
    {
        $description = mb_trim((string) $this->input('description', ''));
        $monitoringIds = $this->input('monitoring_ids', []);

        if (! is_array($monitoringIds)) {
            $monitoringIds = [$monitoringIds];
        }

        $this->merge([
            'name' => mb_trim((string) $this->input('name', '')),
            'description' => $description !== '' ? $description : null,
            'monitoring_ids' => array_values(array_filter(
                array_map(static fn (mixed $monitoringId): string => (string) $monitoringId, $monitoringIds),
                static fn (string $monitoringId): bool => $monitoringId !== ''
            )),
        ]);
    }
}
