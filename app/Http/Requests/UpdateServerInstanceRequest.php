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
            'api_key' => ['nullable', 'string', 'min:16', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
