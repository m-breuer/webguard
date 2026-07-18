<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user'); // Assuming the route parameter is named 'user'

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'package_id' => ['required', 'exists:packages,id'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->input('modal_form') === 'admin-user-edit') {
            $user = $this->route('user');

            return route('admin.users.index', [
                'modal' => 'admin-user-edit',
                'user' => is_object($user) ? $user->getRouteKey() : $user,
            ]);
        }

        return parent::getRedirectUrl();
    }
}
