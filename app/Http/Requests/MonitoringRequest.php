<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\HttpMethod;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use JsonException;

/**
 * Class MonitoringRequest
 *
 * Handles validation logic for creating or updating a monitoring configuration.
 * Applies dynamic rules depending on the selected monitoring type.
 */
class MonitoringRequest extends FormRequest
{
    private bool $invalidHttpHeadersJson = false;

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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MonitoringType::class)],
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

                    if ($this->invalidHttpHeadersJson) {
                        $fail(__('monitoring.validation.headers_invalid_json'));

                        return;
                    }

                    if (! is_array($value)) {
                        $fail(__('monitoring.validation.headers_invalid_format'));
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
            'preferred_location' => ['required', 'string', Rule::exists('server_instances', 'code')->where('is_active', true)],
            'public_label_enabled' => ['boolean'],
            'notification_on_failure' => ['boolean'],
            'maintenance_from' => ['nullable', 'date'],
            'maintenance_until' => ['nullable', 'date', 'after:maintenance_from'],
        ];

        if ($this->isMethod('post')) {
            $rules['target'] = $this->targetRules();
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * Ensures the 'type' field is consistently lowercase before validation.
     */
    protected function prepareForValidation(): void
    {
        $httpHeaders = $this->normalizeHttpHeaders();

        $this->merge([
            'type' => mb_strtolower((string) $this->input('type')),
            'http_headers' => $httpHeaders,
            'public_label_enabled' => $this->boolean('public_label_enabled'),
            'notification_on_failure' => $this->boolean('notification_on_failure'),
        ]);
    }

    /**
     * Get validation rules for the target field during monitoring creation.
     *
     * @return array<int, ValidationRule|callable|string>
     */
    private function targetRules(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>|null|string
     */
    private function normalizeHttpHeaders(): array|null|string
    {
        $httpHeaders = $this->input('http_headers', $this->input('http_header'));

        if (is_array($httpHeaders) || $httpHeaders === null) {
            return $httpHeaders;
        }

        if (! is_string($httpHeaders)) {
            return $httpHeaders;
        }

        $trimmedHeaders = mb_trim($httpHeaders);

        if ($trimmedHeaders === '') {
            return null;
        }

        try {
            $decodedHeaders = json_decode($trimmedHeaders, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalidHttpHeadersJson = true;

            return $httpHeaders;
        }

        if (! is_array($decodedHeaders)) {
            $this->invalidHttpHeadersJson = true;

            return $httpHeaders;
        }

        return $decodedHeaders;
    }
}
