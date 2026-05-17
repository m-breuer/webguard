<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Carbon\Carbon;

class MonitoringChecksRequest extends MonitoringApiRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function days(): ?int
    {
        $days = $this->validated('days');

        return $days === null ? null : (int) $days;
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? 100);
    }

    public function offset(): int
    {
        return (int) ($this->validated('offset') ?? 0);
    }

    public function startDate(): ?Carbon
    {
        $days = $this->days();

        return $days !== null ? now()->subDays($days)->startOfDay() : null;
    }

    public function endDate(): Carbon
    {
        return now()->endOfDay();
    }
}
