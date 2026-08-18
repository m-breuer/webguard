<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MaintenanceWindow;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;

class UpdateMaintenanceRequest extends MaintenanceRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'maintenance_window_id' => ['nullable', 'required_if:mode,recurring', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($this->string('mode')->toString() !== 'recurring' || $validator->errors()->has('maintenance_window_id')) {
                    return;
                }

                $user = $this->user();
                $maintenanceWindowId = $this->string('maintenance_window_id')->toString();

                if (! $user instanceof User || $maintenanceWindowId === '') {
                    return;
                }

                $maintenanceWindow = MaintenanceWindow::query()->find($maintenanceWindowId);

                if (! $maintenanceWindow || ! $maintenanceWindow->isManageableBy($user)) {
                    $validator->errors()->add('maintenance_window_id', __('validation.exists', [
                        'attribute' => __('maintenance.form.recurring'),
                    ]));
                }
            },
        ];
    }
}
