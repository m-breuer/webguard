<?php

declare(strict_types=1);

namespace App\Support\ActivityLog;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Actions\LogActivityAction;

class RedactActivityLogChanges extends LogActivityAction
{
    private const REDACTED = '[redacted]';

    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'auth_password',
        'authorization',
        'bot_token',
        'chat_id',
        'cookie',
        'github_refresh_token',
        'github_token',
        'heartbeat_token',
        'http_body',
        'password',
        'proxy_authorization',
        'refresh_token',
        'remember_token',
        'secret',
        'set_cookie',
        'token',
        'webhook_url',
        'x_api_key',
    ];

    protected function transformChanges(Model $activity): void
    {
        $activity->attribute_changes = $this->redactCollection($activity->attribute_changes ?? collect(), $activity);
        $activity->properties = $this->redactCollection($activity->properties ?? collect(), $activity);
    }

    /**
     * @param  Collection<string, mixed>|array<string, mixed>  $values
     * @return Collection<string, mixed>
     */
    private function redactCollection(Collection|array $values, Model $model): Collection
    {
        $array = $values instanceof Collection ? $values->toArray() : $values;

        return collect($this->redactArray($array, $model));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redactArray(array $values, Model $model, ?string $parentKey = null): array
    {
        foreach ($values as $key => $value) {
            $key = (string) $key;

            if ($this->isSensitiveKey($key, $parentKey, $model)) {
                $values[$key] = self::REDACTED;

                continue;
            }

            if ($key === 'http_headers' && is_array($value)) {
                $values[$key] = $this->redactHttpHeaders($value);

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redactArray($value, $model, $key);
            }
        }

        return $values;
    }

    private function isSensitiveKey(string $key, ?string $parentKey, Model $model): bool
    {
        $normalizedKey = $this->normalizeKey($key);

        if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        if ($parentKey === 'http_headers' && $normalizedKey === 'value') {
            return true;
        }

        if ($parentKey === 'webhook' && $normalizedKey === 'url') {
            return true;
        }

        return $normalizedKey === 'target'
            && $model->subject instanceof Monitoring
            && $model->subject->type === MonitoringType::HEARTBEAT;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function redactHttpHeaders(array $headers): array
    {
        foreach ($headers as $header => $value) {
            $headerName = $this->normalizeKey((string) $header);

            if (in_array($headerName, self::SENSITIVE_KEYS, true)) {
                $headers[$header] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $headers[$header] = $this->redactHttpHeaders($value);
            }
        }

        return $headers;
    }

    private function normalizeKey(string $key): string
    {
        return str_replace('-', '_', mb_strtolower($key));
    }
}
