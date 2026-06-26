<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class MobileLoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function deviceName(): string
    {
        $deviceName = $this->string('device_name')->trim()->toString();

        return $deviceName !== '' ? $deviceName : 'iOS App';
    }

    public function failedLogin(): ValidationException
    {
        return ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }
}
