<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'theme' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
            'notification_channels' => ['nullable', 'array'],
            'notification_channels.slack.webhook_url' => ['nullable', 'url', 'max:2048'],
            'notification_channels.telegram.bot_token' => ['nullable', 'string', 'max:255'],
            'notification_channels.telegram.chat_id' => ['nullable', 'string', 'max:255'],
            'notification_channels.discord.webhook_url' => ['nullable', 'url', 'max:2048'],
            'notification_channels.webhook.url' => ['nullable', 'url', 'max:2048'],
            'monitoring_digest_enabled' => ['nullable', 'boolean'],
            'monitoring_digest_frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'unread_notifications_reminder_enabled' => ['nullable', 'boolean'],
            'unread_notifications_reminder_frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ];

        foreach (NotificationChannel::values() as $channel) {
            $prefix = sprintf('notification_channels.%s', $channel);
            $rules[$prefix] = ['nullable', 'array'];
            $rules[$prefix . '.enabled'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('notification_channels.slack.enabled') && blank($this->input('notification_channels.slack.webhook_url'))) {
                $validator->errors()->add('notification_channels.slack.webhook_url', __('validation.required'));
            }

            if ($this->boolean('notification_channels.discord.enabled') && blank($this->input('notification_channels.discord.webhook_url'))) {
                $validator->errors()->add('notification_channels.discord.webhook_url', __('validation.required'));
            }

            if ($this->boolean('notification_channels.webhook.enabled') && blank($this->input('notification_channels.webhook.url'))) {
                $validator->errors()->add('notification_channels.webhook.url', __('validation.required'));
            }

            if ($this->boolean('notification_channels.telegram.enabled')) {
                if (blank($this->input('notification_channels.telegram.bot_token'))) {
                    $validator->errors()->add('notification_channels.telegram.bot_token', __('validation.required'));
                }

                if (blank($this->input('notification_channels.telegram.chat_id'))) {
                    $validator->errors()->add('notification_channels.telegram.chat_id', __('validation.required'));
                }
            }
        });
    }
}
