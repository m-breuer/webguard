<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Support\MonitoringDateRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

class MonitoringDashboardRequest extends MonitoringApiRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
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

    public function calendarStartDate(): Carbon
    {
        return Date::parse($this->validated('start_date'))->startOfDay();
    }

    public function calendarEndDate(): Carbon
    {
        return Date::parse($this->validated('end_date'))->endOfDay();
    }
}
