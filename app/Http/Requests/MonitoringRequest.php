<?php

namespace App\Http\Requests;

use App\Enums\HttpMethod;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\ServerInstance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Class MonitoringRequest
 *
 * Handles validation logic for creating or updating a monitoring configuration.
 * Applies dynamic rules depending on the selected monitoring type.
 */
class MonitoringRequest extends FormRequest
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
     * Applies conditional validation based on the selected monitoring type.
     *
     * @return array<string, ValidationRule|array|string> The validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MonitoringType::class)],
            'target' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail): void {
                    $type = $this->input('type');

                    if (($type === MonitoringType::HTTP->value || $type === MonitoringType::KEYWORD->value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail(sprintf('The %s must be a valid URL for type %s.', $attribute, $type));
                    }

                    if ($type === MonitoringType::PING->value && ! filter_var($value, FILTER_VALIDATE_IP)) {
                        $fail(sprintf('The %s must be a valid IP address for type %s.', $attribute, $type));
                    }

                    if ($type === MonitoringType::PORT->value && (! filter_var($value, FILTER_VALIDATE_IP) && ! filter_var($value, FILTER_VALIDATE_URL))) {
                        $fail(sprintf('The %s must be a valid IP address or URL for type %s.', $attribute, $type));
                    }
                },
            ],
            'port' => ['nullable', 'required_if:type,port', 'integer', 'min:1', 'max:65535'],
            'keyword' => ['nullable', 'required_if:type,keyword', 'string', 'max:255'],
            'status' => ['required', Rule::enum(MonitoringLifecycleStatus::class)],
            'timeout' => [
                function ($attribute, $value, $fail): void {
                    $user = $this->user();
                    $type = MonitoringType::tryFrom($this->input('type'));

                    if (! in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true)) {
                        if ($this->has('timeout')) {
                            $fail('Timeout configuration is only valid for HTTP or Keyword monitoring.');
                        }

                        return;
                    }

                    if ($value === null) {
                        $fail('The timeout field is required for HTTP or Keyword monitoring.');
                    } elseif (! is_numeric($value) || $value < 1 || $value > 60) {
                        $fail('The timeout must be a number between 1 and 60 seconds.');
                    }
                },
                'max:60',
            ],
            'http_method' => [
                function ($attribute, $value, $fail): void {
                    $type = MonitoringType::tryFrom($this->input('type'));
                    if (! in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true)) {
                        if ($this->has('http_method')) {
                            $fail('HTTP method configuration is only valid for HTTP or Keyword monitoring.');
                        }

                        return;
                    }

                    if ($value && HttpMethod::tryFrom($value) === null) {
                        $fail('The HTTP method must be a valid HTTP method.');
                    }
                },
            ],
            'http_headers' => [
                'nullable',
                function ($attribute, $value, $fail): void {
                    $type = MonitoringType::tryFrom($this->input('type'));
                    if (! in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true)) {
                        if ($this->has('http_headers')) {
                            $fail('Headers are only valid for HTTP or Keyword monitoring.');
                        }

                        return;
                    }

                    if (! is_array($value)) {
                        $fail('Headers must be provided as an array.');
                    }
                },
            ],
            'http_body' => [
                'nullable',
                function ($attribute, $value, $fail): void {
                    $type = MonitoringType::tryFrom($this->input('type'));
                    if (! in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true) && $this->has('http_body')) {
                        $fail('Body content is only valid for HTTP or Keyword monitoring.');
                    }
                },
                'max:2048',
            ],
            'auth_username' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail): void {
                    $type = MonitoringType::tryFrom($this->input('type'));
                    if (! in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true) && $this->has('auth_username')) {
                        $fail('Username for basic auth is only valid for HTTP or Keyword monitoring.');
                    }
                },
            ],
            'auth_password' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail): void {
                    $type = MonitoringType::tryFrom($this->input('type'));
                    if (! in_array($type, [MonitoringType::HTTP, MonitoringType::KEYWORD], true) && $this->has('auth_password')) {
                        $fail('Password for basic auth is only valid for HTTP or Keyword monitoring.');
                    }
                },
            ],
            'preferred_location' => ['required', Rule::enum(ServerInstance::class)],
            'public_label_enabled' => ['boolean'],
            'email_notification_on_failure' => ['boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Ensures the 'type' field is consistently lowercase before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => mb_strtolower((string) $this->input('type')),
            'public_label_enabled' => $this->boolean('public_label_enabled'),
            'email_notification_on_failure' => $this->boolean('email_notification_on_failure'),
        ]);
    }
}
