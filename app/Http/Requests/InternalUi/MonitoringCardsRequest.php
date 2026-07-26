<?php

declare(strict_types=1);

namespace App\Http\Requests\InternalUi;

use Illuminate\Foundation\Http\FormRequest;

class MonitoringCardsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'string', 'distinct'],
        ];
    }

    /**
     * @return list<string>
     */
    public function monitoringIds(): array
    {
        /** @var list<string> $ids */
        $ids = $this->validated('ids');

        return $ids;
    }
}
