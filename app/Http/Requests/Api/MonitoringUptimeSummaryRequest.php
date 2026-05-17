<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Support\Collection;

class MonitoringUptimeSummaryRequest extends MonitoringApiRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'min:1', 'max:10'],
            'days.*' => ['required', 'integer', 'min:1', 'max:3650'],
        ];
    }

    /**
     * @return Collection<int, int>
     */
    public function days(): Collection
    {
        return collect($this->validated('days'))
            ->map(static fn (mixed $day): int => (int) $day)
            ->unique()
            ->sort()
            ->values();
    }
}
