<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ServerInstance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateServerInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $instance = $this->route('server_instance');
        $instanceId = $instance instanceof ServerInstance ? $instance->id : (string) $instance;

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('server_instances', 'code')->ignore($instanceId)],
            'display_name' => ['required', 'string', 'max:100'],
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'region' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['required', 'ipv4'],
            'api_key' => ['nullable', 'string', 'min:16', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->input('modal_form') === 'admin-server-instance-edit') {
            $instance = $this->route('server_instance');

            return route('admin.server-instances.index', [
                'modal' => 'admin-server-instance-edit',
                'server_instance' => is_object($instance) ? $instance->getRouteKey() : $instance,
            ]);
        }

        return parent::getRedirectUrl();
    }
}
