<?php

declare(strict_types=1);

namespace App\Http\Requests\InternalUi;

use App\Enums\MonitoringLifecycleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoringIndexRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:120'],
            'lifecycle_status' => ['nullable', 'string', Rule::enum(MonitoringLifecycleStatus::class)],
        ];
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }

    public function search(): ?string
    {
        $search = $this->validated('search');

        return is_string($search) && $search !== '' ? $search : null;
    }

    public function lifecycleStatus(): ?MonitoringLifecycleStatus
    {
        $status = $this->validated('lifecycle_status');

        return is_string($status) ? MonitoringLifecycleStatus::from($status) : null;
    }
}
