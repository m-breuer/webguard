<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreServerInstanceRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:server_instances,code'],
            'ip_address' => ['required', 'ipv4'],
            'api_key' => ['required', 'string', 'min:16', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->input('modal_form') === 'admin-server-instance-create') {
            return route('admin.server-instances.index', ['modal' => 'admin-server-instance-create']);
        }

        return parent::getRedirectUrl();
    }
}
