<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SupportedLanguage;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Class ProfileRequest
 *
 * Handles validation rules for updating a user's profile including name and email.
 */
class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string> The validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'locale' => ['required', 'string', 'max:10', Rule::in(array_keys($this->getLanguages()))],
            'theme' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
        ];
    }

    public function getLanguages(): array
    {
        return SupportedLanguage::toArray();
    }
}
