<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Support\MonitoringDateRange;

class MonitoringDaysRequest extends MonitoringApiRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer'],
        ];
    }

    public function days(): int
    {
        return (int) ($this->validated('days') ?? 30);
    }

    public function dateRange(): MonitoringDateRange
    {
        return MonitoringDateRange::pastDays($this->days());
    }
}
