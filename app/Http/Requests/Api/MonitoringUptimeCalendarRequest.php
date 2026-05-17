<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

class MonitoringUptimeCalendarRequest extends MonitoringApiRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function startDate(): Carbon
    {
        return Date::parse($this->validated('start_date'))->startOfDay();
    }

    public function endDate(): Carbon
    {
        return Date::parse($this->validated('end_date'))->endOfDay();
    }
}
